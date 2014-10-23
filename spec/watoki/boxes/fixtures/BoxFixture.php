<?php
namespace spec\watoki\boxes\fixtures;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
use watoki\curir\delivery\WebRouter;
use watoki\deli\Path;
use watoki\factory\Factory;
use watoki\factory\providers\CallbackProvider;
use watoki\scrut\Fixture;
use watoki\stores\adapter\FileStoreAdapter;
use watoki\stores\file\raw\File;
use watoki\stores\file\raw\RawFileStore;
use watoki\stores\memory\MemoryStore;
use watoki\stores\memory\SerializerRepository;

class BoxFixture extends Fixture {

    /** @var MemoryStore */
    private $store;

    /** @var WebResponse */
    public $response;

    /** @var array|TestBox[] */
    public $boxes = array();

    /** @var WebRequest */
    private $request;

    /** @var Factory */
    private $factory;

    public function setUp() {
        parent::setUp();
        $this->boxes = array();
        $this->request = new WebRequest(Url::fromString(''), new Path(), 'get');
        $this->factory = new Factory();

        $this->store = new MemoryStore(File::$CLASS, new SerializerRepository());
        /** @noinspection PhpUnusedParameterInspection */
        $this->factory->setProvider(RawFileStore::$CLASS, new CallbackProvider(function ($class, $args) {
            return new FileStoreAdapter($this->store, $args['rootDirectory']);
        }));
    }

    public function givenTheBoxContainer($boxName) {
        $this->givenTheBoxContainer_Responding($boxName, '');
    }

    public function givenTheBoxContainer_Responding($boxName, $boxResponse) {
        $this->givenTheBoxContainer_In_Responding($boxName, 'root', $boxResponse);
    }

    public function givenTheBoxContainer_In_Responding($boxName, $folder, $boxResponse) {
        $this->givenTheBoxContainer_In_WithBody($boxName, $folder, "
            public function doGet() {
                return '$boxResponse';
            }");
    }

    public function givenTheBoxContainer_WithBody($boxName, $body) {
        $this->givenTheBoxContainer_In_WithBody($boxName, 'root', $body);
    }

    public function givenTheBoxContainer_In_WithBody($boxName, $folder, $body) {
        $namespace = $this->spec->getName(false) . ($folder ? '\\' . str_replace('/', '\\', $folder) : '');
        $className = ucfirst($boxName) . WebRouter::SUFFIX;
        $code = "namespace $namespace; class $className extends \\spec\\watoki\\boxes\\fixtures\\TestBox {
            $body
            public function getName() {
                return '$boxName';
            }
            public function getDirectory() {
                return '$folder';
            }
        }";
        eval($code);

        $fileName = ($folder ? $folder . '/' : '') . $className . '.php';
        $this->store->create(new File($code), $fileName);

        $fullClassName = $namespace . '\\' . $className;
        $this->boxes[$boxName] = new $fullClassName($this->factory);

        $this->factory->setSingleton($fullClassName, $this->boxes[$boxName]);
    }

    public function given_Contains($outer, $inner) {
        $this->given_Contains_With($outer, $inner, array());
    }

    public function given_Contains_With($outer, $inner, $args) {
        $this->boxes[$outer]->add($inner, $args);
    }

    public function given_ContainsACollection($outer, $collection) {
        $this->boxes[$outer]->addCollection($collection, array());
    }

    public function given_HasIn_A_With($outer, $collection, $inner, $args) {
        $this->boxes[$outer]->addToCollection($collection, $inner, $args);
    }

    public function givenTheRequestArgument_Is($keyPath, $value) {
        $keys = explode('/', $keyPath);
        $last = array_pop($keys);

        $arguments = $this->request->getArguments();
        foreach ($keys as $key) {
            if (!$arguments->has($key)) {
                $arguments->set($key, new Map());
            }
            $arguments = $arguments->get($key);
        }
        $arguments->set($last, $value);
    }

    public function givenTheMethodIs($method) {
        $this->request->setMethod($method);
    }

    public function givenTheRequestTarget_Is($string) {
        $this->request->setTarget(Path::fromString($string));
    }

    public function given_MapsTargetsTo($outer, $inner) {
        $this->boxes[$outer]->mapTargetsTo($inner);
    }

    public function givenTheRequestFormatIs($string) {
        $this->request->getFormats()->clear();
        $this->request->getFormats()->append($string);
    }

    public function givenTheRequestHasTheHeader($string) {
        $this->request->getHeaders()->set($string, true);
    }

    public function whenIGetTheResponseFrom($path) {
        $this->response = $this->boxes[$path]->respond($this->request);
    }

    public function thenTheResponseShouldBe($body) {
        $this->spec->assertEquals(trim($body), trim($this->response->getBody()));
    }

    public function thenTheResponseShouldBeARedirectionTo($url) {
        $this->spec->assertArrayHasKey(WebResponse::HEADER_LOCATION, $this->response->getHeaders()->toArray());
        $this->spec->assertEquals($url, $this->response->getHeaders()->get(WebResponse::HEADER_LOCATION));
    }

    public function givenTheContextIs($string) {
        $this->request->setContext(Url::fromString($string));
    }

} 