<?php
namespace Aura\Web_Kernel;

use Aura\Project_Kernel\ProjectContainer;

class WebKernelTest extends \PHPUnit_Framework_TestCase
{
    protected $web_kernel;
    
    protected function exec()
    {
        // force into integration mode
        $_ENV['AURA_CONFIG_MODE'] = 'integration';
        
        // always have an HTTP_HOST or request uri won't get put together
        $_SERVER['HTTP_HOST'] = 'example.com';
        
        // retain from the kernel script
        $this->web_kernel = $this->index();
    }
    
    // equivalent to the web/index.php file
    protected function index()
    {
        // run the project kernel
        require dirname(dirname(dirname(dirname(dirname(__DIR__)))))
              . '/vendor/aura/project-kernel/scripts/kernel.php';

        // include the vendor/aura/web-kernel/tests/src dir
        $loader->addPsr4(
            'Aura\\Web_Kernel\\',
            "{$base}/vendor/aura/web-kernel/tests/src"
        );

        // create and invoke a web kernel
        $web_kernel = $di->newInstance('Aura\Web_Kernel\WebKernel');
        $web_kernel();
        
        // done!
        return $web_kernel;
    }
    
    public function testHelloWorld()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/aura/web-kernel/integration/hello';
        $this->exec();
        $expect = 'Hello World!';
        $actual = $this->web_kernel->responder->content;
        $this->assertSame($expect, $actual);
    }
    
    public function testHelloWorldViaIndexPhp()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/index.php/aura/web-kernel/integration/hello';
        $this->exec();
        $expect = 'Hello World!';
        $actual = $this->web_kernel->responder->content;
        $this->assertSame($expect, $actual);
    }
    
    public function testMissingRoute()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/aura/web-kernel/integration/missing-route';
        $this->exec();
        $expect = 'No route for GET /aura/web-kernel/integration/missing-route';
        $actual = trim($this->web_kernel->responder->content);
        $this->assertSame($expect, $actual);
    }
    
    public function testMissingContoller()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/aura/web-kernel/integration/missing-controller';
        $this->exec();
        $expect = <<<EXPECT
Missing controller 'no-such-controller' for GET /aura/web-kernel/integration/missing-controller

Params: array (
  'controller' => 'aura.web_kernel.missing_controller',
  'missing_controller' => 'no-such-controller',
)
EXPECT;
        $actual = trim($this->web_kernel->responder->content);
        $this->assertSame($expect, $actual);
    }
    
    public function testCaughtException()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/aura/web-kernel/integration/throw-exception';
        $this->exec();
        $expect = "Exception 'Exception' thrown for GET /aura/web-kernel/integration/throw-exception";
        $actual = explode(PHP_EOL, $this->web_kernel->responder->content);
        // only check the first line
        $this->assertSame($expect, $actual[0]);
    }
}
