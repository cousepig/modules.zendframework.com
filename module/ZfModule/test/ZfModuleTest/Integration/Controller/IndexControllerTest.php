<?php

namespace ZfModuleTest\Integration\Controller;

use Application\Service;
use ApplicationTest\Integration\Util\AuthenticationTrait;
use ApplicationTest\Integration\Util\Bootstrap;
use EdpGithub\Collection;
use PHPUnit_Framework_MockObject_MockObject;
use stdClass;
use Zend\Http;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use ZfcUser\Entity\User;
use ZfModule\Controller;
use ZfModule\Mapper;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    use AuthenticationTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->setApplicationConfig(Bootstrap::getConfig());
    }

    public function testIndexActionRedirectsIfNotAuthenticated()
    {
        $this->notAuthenticated();

        $this->dispatch('/module');

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('index');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_302);

        $this->assertRedirectTo('/user/login');
    }

    public function testIndexActionFetches100MostRecentlyUpdatedUserRepositories()
    {
        $this->authenticatedAs(new User());

        $repositoryCollection = $this->repositoryCollectionMock();

        $repositoryRetriever = $this->getMockBuilder(Service\RepositoryRetriever::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $repositoryRetriever
            ->expects($this->once())
            ->method('getAuthenticatedUserRepositories')
            ->with($this->equalTo([
                'type' => 'all',
                'per_page' => 100,
                'sort' => 'updated',
                'direction' => 'desc',
            ]))
            ->willReturn($repositoryCollection)
        ;

        $this->getApplicationServiceLocator()
            ->setAllowOverride(true)
            ->setService(
                Service\RepositoryRetriever::class,
                $repositoryRetriever
            )
        ;

        $this->dispatch('/module');

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('index');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_200);
    }

    public function testOrganizationActionRedirectsIfNotAuthenticated()
    {
        $this->notAuthenticated();

        $owner = 'foo';

        $url = sprintf(
            '/module/list/%s',
            $owner
        );

        $this->dispatch($url);

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('organization');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_302);

        $this->assertRedirectTo('/user/login');
    }

    public function testAddActionRedirectsIfNotAuthenticated()
    {
        $this->notAuthenticated();

        $this->dispatch('/module/add');

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('add');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_302);

        $this->assertRedirectTo('/user/login');
    }

    public function testRemoveActionRedirectsIfNotAuthenticated()
    {
        $this->notAuthenticated();

        $this->dispatch('/module/remove');

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('remove');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_302);

        $this->assertRedirectTo('/user/login');
    }

    public function testViewActionSetsHttp404ResponseCodeIfModuleNotFound()
    {
        $vendor = 'foo';
        $module = 'bar';

        $moduleMapper = $this->getMockBuilder(Mapper\Module::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $moduleMapper
            ->expects($this->once())
            ->method('findByName')
            ->with($this->equalTo($module))
            ->willReturn(null)
        ;

        $this->getApplicationServiceLocator()
            ->setAllowOverride(true)
            ->setService(
                'zfmodule_mapper_module',
                $moduleMapper
            )
        ;

        $url = sprintf(
            '/%s/%s',
            $vendor,
            $module
        );

        $this->dispatch($url);

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('not-found');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_404);
    }

    public function testViewActionSetsHttp404ResponseCodeIfRepositoryMetaDataNotFound()
    {
        $vendor = 'foo';
        $module = 'bar';

        $moduleMapper = $this->getMockBuilder(Mapper\Module::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $moduleMapper
            ->expects($this->once())
            ->method('findByName')
            ->with($this->equalTo($module))
            ->willReturn(new stdClass())
        ;

        $repositoryRetriever = $this->getMockBuilder(Service\RepositoryRetriever::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $repositoryRetriever
            ->expects($this->once())
            ->method('getUserRepositoryMetadata')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($module)
            )
            ->willReturn(null)
        ;

        $this->getApplicationServiceLocator()
            ->setAllowOverride(true)
            ->setService(
                'zfmodule_mapper_module',
                $moduleMapper
            )
            ->setService(
                Service\RepositoryRetriever::class,
                $repositoryRetriever
            )
        ;

        $url = sprintf(
            '/%s/%s',
            $vendor,
            $module
        );

        $this->dispatch($url);

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('not-found');
        $this->assertResponseStatusCode(Http\Response::STATUS_CODE_404);
    }

    public function testViewActionCanBeAccessed()
    {
        $vendor = 'foo';
        $module = 'bar';

        $moduleMapper = $this->getMockBuilder(Mapper\Module::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $moduleMapper
            ->expects($this->once())
            ->method('findByName')
            ->with($this->equalTo($module))
            ->willReturn(new stdClass())
        ;

        $repositoryRetriever = $this->getMockBuilder(Service\RepositoryRetriever::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $repositoryRetriever
            ->expects($this->once())
            ->method('getUserRepositoryMetadata')
            ->with(
                $this->equalTo($vendor),
                $this->equalTo($module)
            )
            ->willReturn(new stdClass())
        ;

        $this->getApplicationServiceLocator()
            ->setAllowOverride(true)
            ->setService(
                'zfmodule_mapper_module',
                $moduleMapper
            )
            ->setService(
                Service\RepositoryRetriever::class,
                $repositoryRetriever
            )
        ;

        $url = sprintf(
            '/%s/%s',
            $vendor,
            $module
        );

        $this->dispatch($url);

        $this->assertControllerName(Controller\IndexController::class);
        $this->assertActionName('view');
    }

    /**
     * @link http://stackoverflow.com/a/15907250
     *
     * @param array $repositories
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function repositoryCollectionMock(array $repositories = [])
    {
        $data = new stdClass();
        $data->array = $repositories;
        $data->position = 0;

        $repositoryCollection = $this->getMockBuilder(Collection\RepositoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(function () use ($data) {
                $data->position = 0;
            })
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('current')
            ->willReturnCallback(function () use ($data) {
                return $data->array[$data->position];
            })
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('key')
            ->willReturnCallback(function () use ($data) {
                return $data->position;
            })
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('next')
            ->willReturnCallback(function () use ($data) {
                $data->position++;
            })
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('valid')
            ->willReturnCallback(function () use ($data) {
                return isset($data->array[$data->position]);
            })
        ;

        $repositoryCollection
            ->expects($this->any())
            ->method('count')
            ->willReturnCallback(function () use ($data) {
                return count($data->array);
            })
        ;

        return $repositoryCollection;
    }
}
