<?php declare(strict_types = 1);

namespace liw\web;

/**
 * Class Application
 *
 * @package liw\web
 */
class Application
{
    public $name = 'liw';

    /**
     * @var \stdClass
     */
    private $_components;
    private $_classes;

    /**
     * Application constructor.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->_components = new \stdClass();
        $this->setCoreClasses();
    }

    /**
     * @throws \ReflectionException
     */
    public function __get($className)
    {
        // есть ли в coreClass этот компонент
        if (isset($this->_components->{$className})) {
            return $this->_components->{$className};
        }

        if (
            !class_exists($className)
            && !class_exists(($className = $this->name . '\\' . $className))
        ) {
            throw new \Exception("Класс $className не найден!");
        }

        //мог через set, но через конструктор сделал
        if (method_exists($className, '__construct') !== false) {
            $refMethod = new \ReflectionMethod($className, '__construct'); // ловим инфу о методе внутри класса
            $params = $refMethod->getParameters();

            $re_args = [];

            foreach ($params as $key => $param) {
                if ($param->isDefaultValueAvailable()) {
                    $re_args[$param->name] = $param->getDefaultValue();
                } else {
                    $class = $param->getClass();
                    if ($class !== null) {
                        $re_args[$param->name] = $this->{$class->name};
                    } else {
                        throw new \Exception("Не найден {$class->name} в контейнере");
                    }
                }
            }

            $refClass = new \ReflectionClass($className);
            $class_instance = $refClass->newInstanceArgs($re_args);//создаем новый экземпляр класса
        } else {
            $class_instance = new $className();
        }

        return $this->_components->{$className} = $class_instance;
    }

    public function getCoreClasses()
    {
        // внутри di кладем сразу эти компоненты
        return [
            'Request'    => \Symfony\Component\HttpFoundation\Request::class,
            'Response'   => \Symfony\Component\HttpFoundation\Response::class,
            'Controller' => \liw\Controller::class,
        ];
    }

    protected function setCoreClasses($components = [])
    {
        return $this->_classes = (object)array_merge($this->getCoreClasses(), $components);
    }
}
