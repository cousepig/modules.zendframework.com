<?php

namespace ZfModuleTest\Integration\Controller;

use Application\Service;
use ApplicationTest\Integration\Util\AuthenticationTrait;
use ApplicationTest\Integration\Util\Bootstrap;
use stdClass;
use Zend\Http;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
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
}
