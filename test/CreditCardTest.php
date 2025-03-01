<?php

namespace LaminasTest\Validator;

use ArrayObject;
use Laminas\Validator\CreditCard;
use Laminas\Validator\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function current;

/**
 * @group      Laminas_Validator
 */
class CreditCardTest extends TestCase
{
    /**
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public static function basicValues(): array
    {
        return [
            ['4111111111111111', true],
            ['5404000000000001', true],
            ['374200000000004', true],
            ['4444555566667777', false],
            ['ABCDEF', false],
        ];
    }

    /**
     * Ensures that the validator follows expected behavior
     *
     * @dataProvider basicValues
     */
    public function testBasic(string $input, bool $expected): void
    {
        $validator = new CreditCard();
        $this->assertSame($expected, $validator->isValid($input));
    }

    /**
     * Ensures that getMessages() returns expected default value
     *
     * @return void
     */
    public function testGetMessages()
    {
        $validator = new CreditCard();
        $this->assertEquals([], $validator->getMessages());
    }

    /**
     * Ensures that get and setType works as expected
     *
     * @return void
     */
    public function testGetSetType()
    {
        $validator = new CreditCard();
        $this->assertCount(12, $validator->getType());

        $validator->setType(CreditCard::MAESTRO);
        $this->assertEquals([CreditCard::MAESTRO], $validator->getType());

        $validator->setType(
            [
                CreditCard::AMERICAN_EXPRESS,
                CreditCard::MAESTRO,
            ]
        );
        $this->assertEquals(
            [
                CreditCard::AMERICAN_EXPRESS,
                CreditCard::MAESTRO,
            ],
            $validator->getType()
        );

        $validator->addType(
            CreditCard::MASTERCARD
        );
        $this->assertEquals(
            [
                CreditCard::AMERICAN_EXPRESS,
                CreditCard::MAESTRO,
                CreditCard::MASTERCARD,
            ],
            $validator->getType()
        );
    }

    /**
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public static function visaValues(): array
    {
        return [
            ['4111111111111111', true],
            ['5404000000000001', false],
            ['374200000000004', false],
            ['4444555566667777', false],
            ['ABCDEF', false],
        ];
    }

    /**
     * Test specific provider
     *
     * @dataProvider visaValues
     */
    public function testProvider(string $input, bool $expected): void
    {
        $validator = new CreditCard(CreditCard::VISA);
        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * Test non string input
     *
     * @return void
     */
    public function testIsValidWithNonString()
    {
        $validator = new CreditCard(CreditCard::VISA);
        $this->assertFalse($validator->isValid(['something']));
    }

    /**
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public static function serviceValues(): array
    {
        return [
            ['4111111111111111', false],
            ['5404000000000001', false],
            ['374200000000004', false],
            ['4444555566667777', false],
            ['ABCDEF', false],
        ];
    }

    /**
     * Test service class with invalid validation
     *
     * @dataProvider serviceValues
     */
    public function testServiceClass(string $input, bool $expected): void
    {
        $validator = new CreditCard();
        $this->assertEquals(null, $validator->getService());
        $validator->setService([self::class, 'staticCallback']);
        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public static function optionsValues(): array
    {
        return [
            ['4111111111111111', false],
            ['5404000000000001', false],
            ['374200000000004', false],
            ['4444555566667777', false],
            ['ABCDEF', false],
        ];
    }

    /**
     * Test non string input
     *
     * @dataProvider optionsValues
     */
    public function testConstructionWithOptions(string $input, bool $expected): void
    {
        $validator = new CreditCard(
            [
                'type'    => CreditCard::VISA,
                'service' => [self::class, 'staticCallback'],
            ]
        );

        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * Data provider
     *
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public function jcbValues(): array
    {
        return [
            ['3566003566003566', true],
            ['3528000000000007', true],
            ['3528000000000007', true],
            ['3528000000000007', true],
            ['3088185545477406', false],
            ['3158854390756173', false],
            ['3088936920428541', false],
            ['213193692042852', true],
            ['180012362524156', true],
        ];
    }

    /**
     * Test JCB number validity
     *
     * @dataProvider jcbValues
     * @param string $input
     * @param bool   $expected
     * @group 6278
     * @group 6927
     */
    public function testJcbCard($input, $expected): void
    {
        $validator = new CreditCard(['type' => CreditCard::JCB]);

        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * Data provider
     *
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public function mastercardValues(): array
    {
        return [
            ['4111111111111111', false],
            ['5011642326344731', false],
            ['5130982099822729', true],
            ['2220993834549400', false],
            ['2221006548643366', true],
            ['2222007329134574', true],
            ['2393923057923090', true],
            ['2484350479254492', true],
            ['2518224476613101', true],
            ['2659969950495289', true],
            ['2720992392889757', true],
            ['2721008996056187', false],
        ];
    }

    /**
     * Test mastercard number validity
     *
     * @dataProvider mastercardValues
     * @param string $input
     * @param bool   $expected
     */
    public function testMastercardCard($input, $expected): void
    {
        $validator = new CreditCard(['type' => CreditCard::MASTERCARD]);

        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * Data provider
     *
     * @psalm-return array<array-key, array{0: string, 1: bool}>
     */
    public function mirValues(): array
    {
        return [
            ['3011111111111000', false],
            ['2031343323344731', false],
            ['2200312032822721', true],
            ['2209993834549400', false],
            ['2204001882200999', true],
            ['2202000312124573', true],
            ['2203921957923012', true],
            ['2204150479254495', true],
            ['2201123406612104', true],
            ['2900008996056', false],
            ['2201969950494', true],
            ['2201342387927', true],
            ['2205969950494', false],
        ];
    }

    /**
     * Test mir card number validity
     *
     * @dataProvider mirValues
     * @param string $input
     * @param bool   $expected
     */
    public function testMirCard($input, $expected): void
    {
        $validator = new CreditCard(['type' => CreditCard::MIR]);

        $this->assertEquals($expected, $validator->isValid($input));
    }

    /**
     * Test an invalid service class
     *
     * @return void
     */
    public function testInvalidServiceClass()
    {
        $validator = new CreditCard();
        $this->assertEquals(null, $validator->getService());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback given');
        $validator->setService([self::class, 'nocallback']);
    }

    /**
     * Test a config object
     *
     * @return void
     */
    public function testTraversableObject()
    {
        $options = ['type' => 'Visa'];
        $config  = new ArrayObject($options);

        $validator = new CreditCard($config);
        $this->assertEquals(['Visa'], $validator->getType());
    }

    /**
     * Test optional parameters with config object
     *
     * @return void
     */
    public function testOptionalConstructorParameterByTraversableObject()
    {
        $config = new ArrayObject(
            ['type' => 'Visa', 'service' => [self::class, 'staticCallback']]
        );

        $validator = new CreditCard($config);
        $this->assertEquals(['Visa'], $validator->getType());
        $this->assertEquals([self::class, 'staticCallback'], $validator->getService());
    }

    /**
     * Test optional constructor parameters
     *
     * @return void
     */
    public function testOptionalConstructorParameter()
    {
        $validator = new CreditCard('Visa', [self::class, 'staticCallback']);
        $this->assertEquals(['Visa'], $validator->getType());
        $this->assertEquals([self::class, 'staticCallback'], $validator->getService());
    }

    /**
     * @group Laminas-9477
     */
    public function testMultiInstitute(): void
    {
        $validator = new CreditCard(['type' => CreditCard::MASTERCARD]);
        $this->assertFalse($validator->isValid('4111111111111111'));
        $message = $validator->getMessages();
        $this->assertStringContainsString('not from an allowed institute', current($message));
    }

    public function testEqualsMessageTemplates(): void
    {
        $validator = new CreditCard();
        $this->assertSame(
            [
                CreditCard::CHECKSUM,
                CreditCard::CONTENT,
                CreditCard::INVALID,
                CreditCard::LENGTH,
                CreditCard::PREFIX,
                CreditCard::SERVICE,
                CreditCard::SERVICEFAILURE,
            ],
            array_keys($validator->getMessageTemplates())
        );
        $this->assertEquals($validator->getOption('messageTemplates'), $validator->getMessageTemplates());
    }

    /**
     * @see https://github.com/zendframework/zend-validator/pull/202
     */
    public function testValidatorAllowsExtensionsToDefineAdditionalTypesViaConstants(): void
    {
        $validator = new TestAsset\CreditCardValidatorExtension();
        $this->assertSame($validator, $validator->addType('test_type'));
        $this->assertContains(TestAsset\CreditCardValidatorExtension::TEST_TYPE, $validator->getType());
    }

    /**
     * @return false
     */
    public static function staticCallback(): bool
    {
        return false;
    }
}
