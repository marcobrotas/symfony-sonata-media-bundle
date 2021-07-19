<?php

namespace MediaMonks\SonataMediaBundle\Tests\Functional;

use Exception;
use FilesystemIterator;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use MediaMonks\SonataMediaBundle\Tests\App\AppKernel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Finder\Finder;
use VCR\VCR;

abstract class AbstractBaseFunctionTest extends WebTestCase
{
    use FixturesTrait;

    protected static function getKernelClass()
    {
        return AppKernel::class;
    }

    protected function setUp(): void
    {
        if (version_compare(PHP_VERSION, '7.1.0', '<')) {
            $this->markTestSkipped('Functional tests only run on PHP >= 7.1');
        }

        parent::setUp();

        $this->emptyFolder($this->getMediaPathPublic());
        $this->emptyFolder($this->getMediaPathPrivate());

        VCR::configure()
           ->setCassettePath($this->getFixturesPath())
           ->enableLibraryHooks(['curl'])
           ->setStorage('json');
        VCR::turnOn();
    }

    /**
     * @return string
     */
    protected function getMediaPathPublic()
    {
        return __DIR__ . '/web/media/';
    }

    /**
     * @return string
     */
    protected function getMediaPathPrivate()
    {
        return __DIR__ . '/var/media/';
    }

    /**
     * @return string
     */
    protected function getFixturesPath()
    {
        return __DIR__ . '/var/fixtures/';
    }

    /**
     * @param int $amount
     * @param string $path
     */
    protected function assertNumberOfFilesInPath($amount, $path)
    {
        $finder = new Finder();
        $finder->files()->in($path);
        $this->assertEquals($amount, $finder->count());
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    protected function emptyFolder($path)
    {
        if (file_exists($path)) {
            $di = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
            $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($ri as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
        } else {
            @mkdir($path);
        }

        return true;
    }

    /**
     * @return Client
     */
    protected function getAuthenticatedClient()
    {
        return $this->createClientWithParams([], 'admin', 'admin');
    }

    /**
     * @param Form $form
     * @param array $asserts
     */
    protected function assertSonataFormValues(Form $form, array $asserts)
    {
        foreach ($form->getValues() as $formKey => $formValue) {
            foreach ($asserts as $assertKey => $assertValue) {
                if (strpos($formKey, sprintf('[%s]', $assertKey)) !== false) {
                    $this->assertEquals($assertValue, $formValue);
                }
            }
        }
    }

    /**
     * @param Form $form
     *
     * @return array
     */
    protected function getSonataFormValues(Form $form)
    {
        $values = [];
        foreach ($form->getValues() as $k => $v) {
            if (preg_match('~\[(.*)]~', $k, $matches)) {
                $values[$matches[1]] = $v;
            }
        }

        return $values;
    }

    /**
     * @param Form $form
     * @param array $updates
     */
    protected function updateSonataFormValues(Form $form, array $updates)
    {
        foreach ($form->getValues() as $formKey => $formValue) {
            foreach ($updates as $updateKey => $updateValue) {
                if (strpos($formKey, sprintf('[%s]', $updateKey)) !== false) {
                    $form[$formKey] = $updateValue;
                }
            }
        }
    }

    /**
     * @param Form $form
     *
     * @return mixed
     */
    protected function getSonataFormBaseKey(Form $form)
    {
        foreach ($form->getValues() as $k => $v) {
            if (preg_match('~(.*)\[(.*)]~', $k, $matches)) {
                return $matches[1];
            }
        }

        throw new Exception('Could not find Sonata base key in form');
    }

    /**
     * @param Form $form
     * @param string $file
     *
     * @throws Exception
     */
    protected function setFormBinaryContent(Form $form, $file)
    {
        $baseKey = $this->getSonataFormBaseKey($form);
        $key = sprintf('%s[binaryContent]', $baseKey);
        if (!file_exists($file)) {
            throw new Exception('Upload file does not exist at: ' . $file);
        }
        $form[$key]->upload($file);
    }
}
