<?php

namespace Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories;

use Drupal\drupaleasy_repositories\DrupaleasyRepositories\DrupaleasyRepositoriesPluginBase;

/**
 * Plugin implementation of the drupaleasy_repositories.
 *
 * @DrupaleasyRepositories(
 *   id = "github",
 *   label = @Translation("Remote .yml file"),
 *   description = @Translation("Remote .yml file that includes repository metadata.")
 * )
 */
class GitHub extends DrupaleasyRepositoriesPluginBase {

  /**
   * {@inheritdoc}
   */
  public function validate($uri): bool {
    $pattern = '|^https://(www\.)?github.com/[a-zA-Z0-9_-]+/[a-zA-Z0-9_-]+|';

    if (preg_match($pattern, $uri) === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateHelpText(): string {
    return 'https://github.com/vendor/name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRepo(string $uri): array {
    // Parse the URI for the vendor and name.
    $all_parts = parse_url($uri);
    $pats = explode('/', $all_parts['path']);

    // Set up authentication.
    $this->setAuthentication();

    // Get the repository metadata from the GitHub API.

    // Map repository data to our common format.


    return [];
  }

  protected function setAuthentication(): void {

  }

}
