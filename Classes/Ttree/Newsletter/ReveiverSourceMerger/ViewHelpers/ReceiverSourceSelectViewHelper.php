<?php

namespace Ttree\Newsletter\ReveiverSourceMerger\ViewHelpers;

use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class ReceiverSourceSelectViewHelper extends AbstractFormFieldViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @Flow\Inject
     * @var ReceiverSourceRepository
     */
    protected $receiverSourceRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('multiple', 'string', 'if set, multiple select field');
        $this->registerTagAttribute('size', 'string', 'Size of input field');
    }

    /**
     * Render the tag.
     *
     * @return string rendered tag.
     * @api
     */
    public function render()
    {
        $name = $this->getName();
        if ($this->hasArgument('multiple')) {
            $name .= '[]';
        }

        $this->tag->addAttribute('name', $name);

        $options = $this->getOptions();
        $this->tag->setContent($this->renderOptionTags($options));

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        // register field name for token generation.
        // in case it is a multi-select, we need to register the field name
        // as often as there are elements in the box
        if ($this->hasArgument('multiple') && $this->arguments['multiple'] !== '') {
            $this->renderHiddenFieldForEmptyValue();
            for ($i = 0; $i < count($options); $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }
        } else {
            $this->registerFieldNameForFormTokenGeneration($name);
        }

        return $this->tag->render();
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @return string the rendered option tag
     */
    protected function renderOptionTag($value, $label)
    {
        $output = '<option value="' . htmlspecialchars($value) . '"';
        if ($this->isSelected($value)) {
            $output .= ' selected="selected"';
        }

        $output .= '>' . htmlspecialchars($label) . '</option>';

        return $output;
    }

    /**
     * Render the option tags.
     *
     * @param mixed $value Value to check for
     * @return boolean TRUE if the value should be marked a s selected; FALSE otherwise
     */
    protected function isSelected($value)
    {
        $selectedValue = $this->getSelectedValue();
        if ($value === $selectedValue || (string)$value === $selectedValue) {
            return true;
        }
        if ($this->hasArgument('multiple')) {
            if (is_array($selectedValue) && in_array($value, $selectedValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves the selected value(s)
     *
     * @return mixed value string or an array of strings
     */
    protected function getSelectedValue()
    {
        $value = $this->getValueAttribute();
        if (!is_array($value) && !($value instanceof \Traversable)) {
            return $this->getOptionValueScalar($value);
        }
        $selectedValues = [];
        foreach ($value as $selectedValueElement) {
            $selectedValues[] = $this->getOptionValueScalar($selectedValueElement);
        }
        return $selectedValues;
    }

    /**
     * Get the option value for an object
     *
     * @param mixed $valueElement
     * @return string
     */
    protected function getOptionValueScalar($valueElement)
    {
        if (is_object($valueElement)) {
            if ($this->hasArgument('optionValueField')) {
                return ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
            } elseif ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
                return $this->persistenceManager->getIdentifierByObject($valueElement);
            } else {
                return (string)$valueElement;
            }
        } else {
            return $valueElement;
        }
    }

    /**
     * Render the option tags.
     *
     * @param array $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        if (empty($options)) {
            $options = ['' => ''];
        }
        foreach ($options as $value => $label) {
            $output .= $this->renderOptionTag($value, $label) . chr(10);
        }
        return $output;
    }

    protected function getOptions()
    {
        $options = [];

        foreach ($this->receiverSourceRepository->findAll() as $receiverSource) {
            /* @var ReceiverSource $receiverGroup */
            $options[$this->persistenceManager->getIdentifierByObject($receiverSource)] = $receiverSource->getName();
        }

        return $options;
    }
}
