<?php
declare(strict_types=1);
namespace TYPO3Fluid\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;
use TYPO3Fluid\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestCase;
use TYPO3Fluid\Fluid\ViewHelpers\ElseViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\ThenViewHelper;

/**
 * Testcase for Condition ViewHelper
 */
class AbstractConditionViewHelperTest extends ViewHelperBaseTestCase
{
    public function getStandardTestValues(): array
    {
        return [];
    }

    /**
     * @var AbstractConditionViewHelper|MockObject
     */
    protected $viewHelper;

    public function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(AbstractConditionViewHelper::class, ['getChildren', 'evaluateChildren', 'condition']);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsAllChildrenIfNoThenViewHelperChildExists(): void
    {
        $this->viewHelper->expects($this->any())->method('evaluateChildren')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([]));
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(true));

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context)->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists(): void
    {
        $mockThenViewHelperNode = $this->getMock(ThenViewHelper::class, ['execute'], [], false, false);
        $mockThenViewHelperNode->expects($this->once())->method('execute')->will($this->returnValue('ThenViewHelperResults'));
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([$mockThenViewHelperNode]));
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(true));

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context)->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ThenViewHelperResults', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsValueOfThenArgumentIfItIsSpecified(): void
    {
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(true));
        $arguments = [
            'then' => 'ThenArgument',
        ];

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context, $this->viewHelper->getArguments()->assignAll($arguments))->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ThenArgument', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsEmptyStringIfChildNodesOnlyContainElseViewHelper(): void
    {
        $mockElseViewHelperNode = $this->getMock(ElseViewHelper::class, ['execute'], [], false, false);
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([$mockElseViewHelperNode]));
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(true));
        $this->viewHelper->expects($this->never())->method('evaluateChildren')->will($this->returnValue('Child nodes'));

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context)->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function renderElseChildReturnsEmptyStringIfConditionIsFalseAndNoElseViewHelperChildExists(): void
    {
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([]));
        $actualResult = $this->viewHelper->_call('renderElseChild');
        $this->assertEquals(null, $actualResult);
    }

    /**
     * @test
     */
    public function renderElseChildRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists(): void
    {
        $mockElseViewHelperNode = $this->getMock(ElseViewHelper::class, ['execute'], [], false, false);
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(false));
        $mockElseViewHelperNode->expects($this->once())->method('execute')->will($this->returnValue('ElseViewHelperResults'));
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([$mockElseViewHelperNode]));
        $arguments = [
            'condition' => false,
        ];

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context, $this->viewHelper->getArguments()->assignAll($arguments))->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ElseViewHelperResults', $actualResult);
    }

    /**
     * @test
     */
    public function renderElseChildReturnsEmptyStringIfConditionIsFalseAndElseViewHelperChildIfArgumentConditionIsFalseToo(): void
    {
        $context = new RenderingContextFixture();
        $mockElseViewHelperNode = $this->getMock(ElseViewHelper::class, ['execute'], [], false, false);
        $mockElseViewHelperNode->onOpen($context, $mockElseViewHelperNode->getArguments()->assignAll(['if' => false]));
        $mockElseViewHelperNode->expects($this->never())->method('execute');


        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(false));
        $this->viewHelper->expects($this->any())->method('getChildren')->will($this->returnValue([$mockElseViewHelperNode]));

        $actualResult = $this->viewHelper->onOpen($context)->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals(null, $actualResult);
    }

    /**
     * @test
     */
    public function thenArgumentHasPriorityOverChildNodesIfConditionIsTrue(): void
    {
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(true));
        $this->viewHelper->expects($this->never())->method('getChildren');
        $arguments = [
            'then' => 'ThenArgument',
        ];

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context, $this->viewHelper->getArguments()->assignAll($arguments))->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ThenArgument', $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsValueOfElseArgumentIfConditionIsFalse(): void
    {
        $arguments['else'] = 'ElseArgument';

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context, $this->viewHelper->getArguments()->assignAll($arguments))->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ElseArgument', $actualResult);
    }

    /**
     * @test
     */
    public function elseArgumentHasPriorityOverChildNodesIfConditionIsFalse(): void
    {
        $mockElseViewHelperNode = $this->getMock(ElseViewHelper::class, ['execute'], [], false, false);
        $mockElseViewHelperNode->expects($this->never())->method('execute');
        $this->viewHelper->expects($this->any())->method('condition')->will($this->returnValue(false));

        $arguments['else'] = 'ElseArgument';

        $context = new RenderingContextFixture();
        $actualResult = $this->viewHelper->onOpen($context, $this->viewHelper->getArguments()->assignAll($arguments))->execute($context, $this->viewHelper->getArguments());
        $this->assertEquals('ElseArgument', $actualResult);
    }
}
