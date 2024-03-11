<?php

namespace Drupal\Tests\action_link\Kernel;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests access to action links.
 *
 * @group action_link
 */
class ActionLinkAccessKernelTest extends KernelTestBase implements LoggerInterface {

  use UserCreationTrait;
  use RfcLoggerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state action manager.
   *
   * @var \Drupal\action_link\StateActionManager
   */
  protected $stateActionManager;

  /**
   * The action_link storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionLinkStorage;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  protected ActionLinkInterface $actionLink;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'action_link',
    'action_link_test_plugins',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('logger.factory')->addLogger($this);

    $this->installConfig('user');
    $this->installConfig('system');
    $this->installEntitySchema('user');

    $this->state = $this->container->get('state');
    $this->messenger = $this->container->get('messenger');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->stateActionManager = $this->container->get('plugin.manager.action_link_state_action');
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');

    $this->actionLink = $this->actionLinkStorage->create([
      'id' => 'test_mocked_control',
      'label' => 'Test',
      'plugin_id' => 'test_mocked_control',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $this->actionLink->save();

    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    // Create the anonymous user, otherwise we get the user context error.
    // See https://www.drupal.org/project/drupal/issues/3056234.
    $values = [
      'uid' => 0,
      'status' => 0,
      'name' => '',
    ];
    $this->createUser(values: $values);

    // First call to createUser() creates user 1, so we do this manually because
    // we don't want our test users to be the superuser.
    $this->createUser();
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    // Fail the test for log errors. This is so that failures during an HTTP
    // kernel request fail the test with a meaningful error rather than just
    // reporting the returned 500 HTTP status.
    if ($level <= RfcLogLevel::ERROR) {
      $message = strtr($message, $context);

      $level_label = \Drupal\Core\Logger\RfcLogLevel::getLevels()[$level];
      $this->fail("Log $level_label: $message");
    }
  }

  /**
   * Data provider for the test.
   */
  public function dataLinkAccess() {
    $data = [];
    foreach ([FALSE, TRUE] as $main_permission_access) {
      // TODO: rename.
      foreach ([FALSE, TRUE] as $permission_access) {
        foreach ([FALSE, TRUE] as $operand_general_access) {
          foreach ([FALSE, TRUE] as $operand_state_access) {
            foreach ([FALSE, TRUE] as $operability) {
              foreach ([FALSE, TRUE] as $reachable) {
                // Can't use array_map() because of variable variable name.
                $keys = [];
                foreach ([
                  'main_permission_access',
                  'permission_access',
                  'operand_general_access',
                  'operand_state_access',
                  'operability',
                  'reachable',
                ] as $var_name) {
                  $keys[] = ($$var_name ? '' : 'no-') . $var_name;
                }
                $set_key = implode(':', $keys);

                $data[$set_key] = [
                  $set_key,
                  $main_permission_access,
                  $permission_access,
                  $operand_general_access,
                  $operand_state_access,
                  $operability,
                  $reachable,
                ];
              }
            }
          }
        }
      }
    }
    return $data;
  }

  /**
   * Comment out the dataProvider annotation for development.
   *
   * dataProvider dataLinkAccess
   */
  public function testLinkAccess(...$parameters) {
    if (!empty($parameters)) {
      $this->doTestLinkAccess(...$parameters);
    }
    else {
      foreach ($this->dataLinkAccess() as $dataset) {
        $this->doTestLinkAccess(...$dataset);
      }
    }
  }

  /**
   * Actual test code for testLinkAccess().
   *
   * @see https://www.drupal.org/project/drupal/issues/3382241
   */
  public function doTestLinkAccess(
    string $set_key,
    bool $main_permission_access,
    bool $permission_access,
    bool $operand_general_access,
    bool $operand_state_access,
    bool $operability,
    bool $reachable,
  ) {
    // Set up the user. The users are static variables so they're only created
    // once in a single-test scenario, and created here so only one user is
    // created in a dataset scenario.
    static $user_no_access;
    static $user_with_access;
    if ($main_permission_access && !$user_with_access) {
      $user_with_access = $this->createUser(["use {$this->actionLink->id()} action links"]);
    }
    elseif (!$user_no_access) {
      $user_no_access = $this->createUser();
    }
    // TODO: This is badly-named, as it's state-specific, or should be.
    // TODO document why no access is neutrak not forbidden!
    $this->state->set('test_mocked_control:checkPermissionStateAccess', $permission_access ? AccessResult::allowed() : AccessResult::neutral());
    $this->state->set('test_mocked_control:checkOperandGeneralAccess', $operand_general_access ? AccessResult::allowed() : AccessResult::neutral());
    $this->state->set('test_mocked_control:checkOperandStateAccess', $operand_state_access ? AccessResult::allowed() : AccessResult::neutral());
    $this->state->set('test_mocked_control:checkOperability', $operability);
    $this->state->set('test_mocked_control:getNextStateName', $reachable ? 'cake' : NULL);

    // The different dimensions are:
    //
    // - general permission (in the entity, not mocking it, just set the perm.)
    // - general operand permission -- checkOperandGeneralAccess
    // - state/direction permission (on the plugin) -- checkPermissionStateAccess
    // - operand state/direction permission -- checkOperandStateAccess

    // - general logic - operability - checkOperability
    // - state logic - reachability  - getNextStateName

    // Checking access to routes requires the current user to be set up.
    // @todo Change this when we add proxy functionality.
    // $user_no_access = $this->setUpCurrentUser();

    // Generate the links.
    $links = $this->actionLink->getStateActionPlugin()->buildLinkArray(
      $this->actionLink,
      ($main_permission_access ? $user_with_access : $user_no_access),
    );


    if (!$operability) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
      // ERRRM so no operability is the ONLY case where we get empty links array?
      // NOT IDEAL FOR PERFORMANCE. We need an overall control switch.
      // a 'use THIS action link in general'
      // AND 'use ANY STATE' master permission
    }
    elseif (!$main_permission_access && !$permission_access) {
      // No access to the action link entity, because there is neither the
      // general permission nor the state permission.
      $this->assertArrayHasKey('change', $links, "The links array has a link for the 'change' direction for $set_key.");
      $this->assertEmpty($links['change']['#link'], "The direction link is empty for $set_key.");
    }
    elseif (!$operand_general_access && !$operand_state_access) {
      // No access to the operand, because there is neither the general
      // permission nor the state permission.
      $this->assertArrayHasKey('change', $links, "The links array has a link for the 'change' direction for $set_key.");
      $this->assertEmpty($links['change']['#link'], "The direction link is empty for $set_key.");
    }
    elseif (!$reachable) {
      $this->assertArrayHasKey('change', $links, "The links array has a link for the 'change' direction for $set_key.");
      $this->assertEmpty($links['change']['#link'], "The direction link is empty for $set_key.");
    }
    else {
      $this->assertArrayHasKey('change', $links, "The links array has a link for the 'change' direction for $set_key.");
      $this->assertNotEmpty($links['change']['#link'], "The direction link is not empty for $set_key.");
      $this->assertInstanceOf(\Drupal\Core\Url::class, $links['change']['#link']['#url']);
    }

    $http_kernel = $this->container->get('http_kernel');

    // Make a request to the action link route.
    $this->setCurrentUser($main_permission_access ? $user_with_access : $user_no_access);
    $uid = ($main_permission_access ? $user_with_access : $user_no_access)->id();
    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$uid}");
    $response = $http_kernel->handle($request);

    if (!$operability) {
      $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode(), "Request got a 403 for $set_key.");
    }
    elseif (!$main_permission_access && !$permission_access) {
      $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode(), "Request got a 403 for $set_key.");
    }
    elseif (!$operand_general_access && !$operand_state_access) {
      $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode(), "Request got a 403 for $set_key.");
    }
    elseif (!$reachable) {
      $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode(), "Request got a redirect for $set_key.");
    }
    else {
      $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode(), "Request got a redirect for $set_key.");
    }



    return;

    // Checks which are stateless mean no links.
    if (!$main_permission_access) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$operand_general_access) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$operability) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$permission_access) {
      // need to distinguis bet empty link and real link
      // !!! BUG! Need to 'AND' all the state-specific accesses.
      // dump(array_keys($links));
      // dump(array_keys($links['change']));
      // dump($links['change']['#link']);
      $this->assertArrayHasKey('change', $links, "The links array has a link for the 'change' direction for $set_key.");
      return;
    }



    return;




    // WTF NO operand_general_access !!!
    //

    // THis should be simple:
    //  - not allowed to use general? NOTHING
    //  - not allower to use a specific state? Empty link

    // Checking access to routes requires the current user to be set up.
    // @todo Change this when we add proxy functionality.
    $user_no_access = $this->setUpCurrentUser();

    // Generate the links.
    $links = $this->actionLink->getStateActionPlugin()->buildLinkArray($this->actionLink, $user_no_access);

    // dump($set_key);
    if (!$permission_access) {
      // No permission access always means no links. OR DOES IT? What kind of permissions are we checking
      // FUCK we are mixing general permission and state permission???
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$operand_general_access) {
      // No general operand access always means no link
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$operand_state_access) {
      // dump($links);
      return;
    }
    return;



    // EXCEPT BAD DESIGN - when no next state, we don't check
    // operand access!!!
    if (!$permission_access || !$operand_state_access) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$operability) {
      $this->assertEmpty($links, "The links array is empty for $set_key.");
    }
    elseif (!$reachable) {
      dump($links);
    }
    else {
      dump("NOT CHECKED $set_key");
    }


    $http_kernel = $this->container->get('http_kernel');

    // Make a request to the action link route.
    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);

    if (!$permission_access || !$operand_access) {
      $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
  }


}