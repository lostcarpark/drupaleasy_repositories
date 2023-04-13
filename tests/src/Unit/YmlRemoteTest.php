<?php

namespace Drupal\Tests\drupaleasy_repositories\Unit;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test description.
 *
 * @group drupaleasy_repositories
 */
class YmlRemoteTest extends UnitTestCase {

  /**
   * The YmlRemote plugin class.
   *
   * @var \Drupal\drupaleasy_repositories\Plugin\DrupaleasyRepositories\YmlRemote
   */
  protected YmlRemote $ymlRemote;

  /**
   * Spoof the messenger object.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Spoof the key repository.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected MessengerInterface|MockObject $keyRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the messenger object.
    $this->messenger = $this->getMockBuilder('\Drupal\Core\Messenger\MessengerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the key.repository object.
    $this->keyRepository = $this->getMockBuilder('\Drupal\key\KeyRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->ymlRemote = new YmlRemote([], '', [], $this->messenger, $this->keyRepository);
  }

  /**
   * Test that the help text returns as expected.
   *
   * @covers ::validateHelpText
   * @test
   */
  public function testValidateHelpText(): void {
    self::assertEquals('https://anything.anything/anything/anything.yml (or "http")', $this->ymlRemote->validateHelpText(), 'Help text does not match.');
  }

  /**
   * Data provider for testValidate().
   *
   * @return array
   *   Array of test strings and results.
   */
  public function validateProvider(): array {
    return [
      [
        'A test string',
        FALSE,
      ],
      [
        'http://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://www.mysite.com/anything.yaml',
        TRUE,
      ],
      [
        '/var/www/html/anything.yaml',
        FALSE,
      ],
      [
        'https://www.mysite.com/some%20directory/anything.yml',
        TRUE,
      ],
      [
        'https://www.my-site.com/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://localhost/some%20directory/anything.yaml',
        TRUE,
      ],
      [
        'https://dev.www.mysite.com/anything.yml',
        TRUE,
      ],
      [
        'https://dev.www.mysite.com/ANYTHING.yml',
        TRUE,
      ],
      [
        'ftp://dev.www.mysite.com/anything.yml',
        FALSE,
      ],
    ];
  }

  /**
   * Test that the URL validator works.
   *
   * @dataProvider validateProvider
   *
   * @covers ::validate
   * @test
   */
  public function testValidate(string $testString, bool $expected): void {
    self::assertEquals($expected, $this->ymlRemote->validate($testString), "Validation of '$testString' does not return '$expected'.");
  }

  /**
   * Test that a repo can be read properly.
   *
   * @covers ::getRepo
   * @test
   */
  public function testGetRepo(): void {
    $repo = $this->ymlRemote->getRepo(__DIR__ . '/../../assets/batman-repo.yml');
    $repo = reset($repo);
    self::assertEquals('The Batman repository', $repo['label'], 'Label does not match.');
    self::assertEquals('This is where Batman keeps all his crime-fighting code.', $repo['description'], 'Description does not match.');
  }

}
