<?php

/*
 * This file is part of the Ivory Serializer bundle package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\SerializerBundle\Tests\Type;

use Ivory\Serializer\Format;
use Ivory\Serializer\Navigator\Navigator;
use Ivory\Serializer\Registry\TypeRegistry;
use Ivory\Serializer\Serializer;
use Ivory\SerializerBundle\Type\FormErrorType;
use Ivory\SerializerBundle\Type\FormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author GeLo <geloen.eric@gmail.com>
 */
class FormTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->translator
            ->expects($this->any())
            ->method('transChoice')
            ->will($this->returnArgument(1));

        $this->serializer = new Serializer(new Navigator(TypeRegistry::create([
            FormInterface::class => new FormType(),
            FormError::class     => new FormErrorType($this->translator),
        ])));
    }

    /**
     * @param string $name
     * @param string $data
     * @param string $format
     *
     * @dataProvider serializeProvider
     */
    public function testSerialize($name, $data, $format)
    {
        $this->assertSame($this->getDataSet($name, $format), $this->serializer->serialize($data, $format));
    }

    /**
     * @param string $name
     * @param string $data
     * @param string $format
     *
     * @dataProvider formErrorProvider
     */
    public function testSerializeFormErrorWithoutTranslator($name, $data, $format)
    {
        $this->serializer = new Serializer(new Navigator(TypeRegistry::create([
            FormError::class => new FormErrorType(),
        ])));

        $this->assertSame(
            $this->getDataSet($name.'_no_translator', $format),
            $this->serializer->serialize($data, $format)
        );
    }

    /**
     * @param string $format
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Deserializing a "Symfony\Component\Form\Form" is not supported.
     *
     * @dataProvider formatProvider
     */
    public function testDeserializeForm($format)
    {
        $this->serializer->deserialize($this->getDataSet('form', $format), Form::class, $format);
    }

    /**
     * @param string $format
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Deserializing a "Symfony\Component\Form\FormError" is not supported.
     *
     * @dataProvider formatProvider
     */
    public function testDeserializeFormError($format)
    {
        $this->serializer->deserialize($this->getDataSet('form', $format), FormError::class, $format);
    }

    /**
     * @return mixed[]
     */
    public function serializeProvider()
    {
        $factory = Forms::createFormFactory();
        $preferFQCN = method_exists(AbstractType::class, 'getBlockPrefix');

        $childForm = $factory->createNamedBuilder(
            'bar',
            $preferFQCN ? SymfonyFormType::class : 'form',
            null,
            ['error_bubbling' => false]
        );

        $childForm
            ->add('baz')
            ->add('bat');

        $form = $factory->createBuilder()
            ->add('foo')
            ->add($childForm)
            ->add('button', $preferFQCN ? ButtonType::class : 'button')
            ->add('submit', $preferFQCN ? SubmitType::class : 'submit')
            ->getForm();

        $form->addError(new FormError('error'));
        $form->get('foo')->addError(new FormError('foo_error'));
        $form->get('bar')->addError(new FormError('bar_error'));
        $form->get('bar')->get('baz')->addError(new FormError('baz_error'));

        return $this->expandCases(array_merge($this->formErrorCases(), [
            ['form', $form],
        ]));
    }

    /**
     * @return mixed[]
     */
    public function formErrorProvider()
    {
        return $this->expandCases($this->formErrorCases());
    }

    /**
     * @return string[][]
     */
    public function formatProvider()
    {
        return [
            [Format::CSV],
            [Format::JSON],
            [Format::XML],
            [Format::YAML],
        ];
    }

    /**
     * @return mixed[]
     */
    private function formErrorCases()
    {
        $formError = new FormError('error');
        $translatedFormError = new FormError('trans_error', 'trans', []);
        $pluralizedFormError = new FormError('plural_error', 'trans', [], 'plural');

        return [
            ['form_error', $formError],
            ['form_error_translated', $translatedFormError],
            ['form_error_pluralized', $pluralizedFormError],
        ];
    }

    /**
     * @param mixed[] $cases
     *
     * @return mixed[]
     */
    private function expandCases(array $cases)
    {
        $providers = [];

        foreach ([Format::CSV, Format::JSON, Format::XML, Format::YAML] as $format) {
            foreach ($cases as $case) {
                $case[] = $format;
                $providers[] = $case;
            }
        }

        return $providers;
    }

    /**
     * @param string $name
     * @param string $format
     *
     * @return string
     */
    private function getDataSet($name, $format)
    {
        $extension = $format;

        if ($extension === Format::YAML) {
            $extension = 'yml';
        }

        return file_get_contents(__DIR__.'/../Fixtures/Data/'.strtolower($format).'/'.$name.'.'.strtolower($extension));
    }
}
