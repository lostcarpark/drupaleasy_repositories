<?php

namespace Drupal\Tests\drupaleasy_repositories\Functional;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\drupaleasy_repositories\Traits\RepositoryContentTypeTrait;
use Drupal\Tests\WebAssert;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class AddYmlRepoTest extends BrowserTestBase {

  use RepositoryContentTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'drupaleasy_repositories',
  ];

  /**
   * {@inheritdoc}
   * @throws EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    $config = $this->config(name: 'drupaleasy_repositories.settings');
    $config->set('repositories', ['yml_remote' => 'yml_remote']);
    $config->save();

    // Create and login as a Drupal admin user with permission to access
    // the DrupalEasy Repositories Settings page. This is UID=2 because UID=1
    // is created by FunctionalTestSetupTrait. The root user can be accessed
    // with $this->rootUser.
    $admin_user = $this->drupalCreateUser(['configure drupaleasy repositories']);
    $this->drupalLogin($admin_user);

    // $this->createRepositoryContentType();
    // // Create User entity Repository URL field.
    // FieldStorageConfig::create([
    //   'field_name' => 'field_repository_url',
    //   'type' => 'link',
    //   'entity_type' => 'user',
    //   'cardinality' => -1,
    // ])->save();
    // FieldConfig::create([
    //   'field_name' => 'field_repository_url',
    //   'entity_type' => 'user',
    //   'bundle' => 'user',
    //   'label' => 'Repository URL',
    // ])->save();

    /** @var EntityDisplayRepository $entity_display_repository  */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $entity_display_repository->getFormDisplay('user', 'user', 'default')
      ->setComponent('field_repository_url', ['type' => 'link_default'])
      ->save();
  }

  /**
   * Test that the settings page can be reached and works as expected.
   *
   * This tests that an admin user can access the settings page, select a
   * plugin to enable, and submit the page successfully.
   *
   * @test
   * @throws ExpectationException
   */
  public function testSettingsPage(): void {
    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to the DrupalEasy Repositories Settings page and confirm we
    // can reach it.
    $this->drupalGet('/admin/config/services/repositories');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);
    $session->titleEquals('DrupalEasy repositories settings | Drupal');
    $session->pageTextContains('DrupalEasy repositories settings');

    // Select the "Remote .yml file" checkbox and submit the form.
    $edit = [
      'edit-repositories-yml-remote' => 'yml_remote',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->responseContains('The configuration options have been saved.');
    $session->checkboxChecked('edit-repositories-yml-remote');
    // $session->checkboxNotChecked('edit-repositories-github');
  }

  /**
   * Create user and log in.
   *
   * @return WebAssert
   *   The current browser session.
   *
   * @throws EntityStorageException
   * @throws ExpectationException
   */
  public function createAndLoginUser(): WebAssert {
    // Create and login as a Drupal user with permission to access
    // content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    $session = $this->assertSession();

    // Navigate to their edit profile page and confirm we can reach it.
    $this->drupalGet('/user/' . $user->id() . '/edit');
    // Try this with a 500 status code to see it fail.
    $session->statusCodeEquals(200);

    return $session;
  }

  /**
   * Get the full path to the modules directory.
   *
   * @return string
   */
  public function getModuleFullPath(): string {
    // Get the full path to the test .yml file.
    /** @var ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    /** @var Extension $module */
    $module = $module_handler->getModule('drupaleasy_repositories');
    return AddYmlRepoTest . php\Drupal::request()->getUri() . $module->getPath();
  }

  /**
   * Submit user profile form to save repository.
   *
   * @param WebAssert $session
   *   The current user session.
   * @param string $url
   *   The URL to save.
   *
   * @throws ExpectationException
   */
  public function saveRepository(WebAssert $session, string $url): void {
    $edit = [
      'field_repository_url[0][uri]' => $url,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->responseContains('The changes have been saved.');
  }

  /**
   * Test that a yml repo can be added to profile by a user.
   *
   * This tests that a yml-based repo can be added to a user's profile and
   * that a repository node is successfully created upon saving the profile.
   *
   * @test
   * @throws EntityStorageException
   * @throws ExpectationException
   * @throws PluginNotFoundException
   * @throws InvalidPluginDefinitionException
   */
  public function testAddYmlRepo(): void {
    $session = $this->createAndLoginUser();

    // Add the test .yml file path and submit the form.
    $this->saveRepository($session, $this->getModuleFullPath() . '/tests/assets/batman-repo.yml');

    // We can't check for the following message unless we also have the future
    // drupaleasy_notify modules enabled.
    // $session->responseContains('The repo named <em class="placeholder">The Batman repository</em> has been created');.
    // Find the new repository node.
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository')->accessCheck(FALSE);
    $results = $query->execute();
    $session->assert(count($results) === 1, 'Either 0 or more than 1 repository nodes were found.');

    $entity_type_manager = \Drupal::entityTypeManager();
    $node_storage = $entity_type_manager->getStorage('node');
    /** @var NodeInterface $node */
    $node = $node_storage->load(reset($results));

    // Check values.
    $session->assert($node->field_machine_name->value == 'batman-repo', 'Machine name does not match.');
    $session->assert($node->field_source->value == 'yml_remote', 'Source does not match.');
    $session->assert($node->title->value == 'The Batman repository', 'Label does not match.');
    $session->assert($node->field_description->value == 'This is where Batman keeps all his crime-fighting code.', 'Description does not match.');
    $session->assert($node->field_number_of_issues->value == '6', 'Number of issues does not match.');
  }

  /**
   * Test that a yml repo can be removed from profile by a user.
   *
   * @return void
   * @throws ExpectationException
   * @throws EntityStorageException
   */
  function testRemoveYmlRepo(): void {
    $session = $this->createAndLoginUser();

    // Add the test .yml file path and submit the form.
    $this->saveRepository($session, $this->getModuleFullPath(). '/tests/assets/batman-repo.yml');

    // Add the test .yml file path and submit the form.
    $this->saveRepository($session, '');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'repository')->accessCheck(FALSE);
    $results = $query->execute();
    // Verify that no results returned.
    $session->assert(count($results) === 0, 'The repository was not deleted.');
  }

}
