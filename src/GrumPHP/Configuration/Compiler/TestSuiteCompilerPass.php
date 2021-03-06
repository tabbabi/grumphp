<?php

namespace GrumPHP\Configuration\Compiler;

use GrumPHP\Collection\TestSuiteCollection;
use GrumPHP\TestSuite\TestSuite;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TestSuiteCompilerPass
 *
 * @package GrumPHP\Configuration\Compiler
 */
class TestSuiteCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $testSuites = $container->getParameter('testsuites');
        $registeredTasks = (array) $container->getParameter('grumphp.tasks.registered');
        $optionsResolver = $this->createOptionsResolver($registeredTasks);

        $collection = new TestSuiteCollection();
        foreach ($testSuites as $name => $config) {
            $config = $optionsResolver->resolve($config);
            $collection->set($name, new TestSuite($name, $config['tasks']));
        }

        $container->setParameter('grumphp.testsuites', $collection);
    }

    /**
     * @param array $registeredTasks
     *
     * @return OptionsResolver
     */
    private function createOptionsResolver(array $registeredTasks)
    {
        $options = new OptionsResolver();
        $options->setRequired(['tasks']);
        $options->setAllowedTypes('tasks', ['array']);
        $options->setAllowedValues('tasks', function (array $value) use ($registeredTasks) {
            foreach ($value as $task) {
                if (!in_array($task, $registeredTasks, true)) {
                    throw new InvalidOptionsException(sprintf(
                        'The testsuite option "tasks" contains the unknow task "%s". Expected one of %s',
                        $task,
                        implode(',', $registeredTasks)
                    ));
                }
            }

            return true;
        });

        return $options;
    }
}
