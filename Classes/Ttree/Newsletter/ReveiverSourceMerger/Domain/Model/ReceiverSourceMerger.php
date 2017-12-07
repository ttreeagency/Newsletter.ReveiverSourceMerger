<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 13:15
 */

namespace Ttree\Newsletter\ReveiverSourceMerger\Domain\Model;

use Sandstorm\Newsletter\Domain\Model\ReceiverGroup;
use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use Ttree\JsonStore\Domain\Model\Document;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Files;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class ReceiverSourceMerger extends ReceiverSource
{
    /**
     * @var ReceiverSourceRepository
     * @Flow\Inject
     */
    protected $receiverSourceRepository;

    /**
     * @var array
     * @ORM\Column(type="flow_json_array")
     */
    protected $sources = [];

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $defaults;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="receiverGroupCache", package="Sandstorm.Newsletter")
     * @Flow\Transient
     */
    protected $receiverGroupCache;

    public function getType()
    {
        return 'TtreeReceiverSourceMerger';
    }

    public function initializeOrUpdate()
    {
        $output = [];
        $defaults = $this->prepareDefaultValues();
        foreach ($this->sources as $identifier) {
            /** @var ReceiverSource $receiverSource */
            $receiverSource = $this->receiverSourceRepository->findByIdentifier($identifier);
            if ($receiverSource === null) {
                continue;
            }
            $receiverSource->initializeOrUpdate();
            $handle = fopen($receiverSource->getSourceFileName(), 'r');
            while (($line = fgets($handle)) !== false) {
                $data = \json_decode($line, true);
                if (!isset($data['email'])) {
                    continue;
                }
                foreach ($defaults as $defaultValue) {
                    $currentValue = Arrays::getValueByPath($data, $defaultValue['path']);
                    if ($currentValue !== null) {
                        continue;
                    }
                    $data = Arrays::setValueByPath($data, $defaultValue['path'], $defaultValue['value']);
                }
                $output[$data['email']] = \json_encode($data);
            }
        }

        Files::createDirectoryRecursively(dirname($this->getSourceFileName()));

        file_put_contents($this->getSourceFileName(), implode("\n", $output));

        parent::initializeOrUpdate();
    }

    /**
     * @return array
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @param array $sources
     */
    public function setSources($sources)
    {
        foreach ($sources as $key => $value) {
            if ($this->persistenceManager->getIdentifierByObject($this) === $value) {
                unset($sources[$key]);
            }
        }
        $this->sources = \array_values($sources);
    }

    protected function prepareDefaultValues()
    {
        if (trim($this->defaults) === '') {
            return [];
        }
        $defaults = \explode(chr(10), $this->defaults);
        $defaults = \array_map(function ($value) {
            list($path, $value) = Arrays::trimExplode(':', $value);
            return [
                'path' => $path,
                'value' => $value
            ];
        }, $defaults);
        return $defaults;
    }

    /**
     * @return string
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param string $defaults
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    public function getConfigurationAsString()
    {
        // TODO: Implement getConfigurationAsString() method.
    }

    public function getSourceFileName()
    {
        return $this->receiverGroupCache . '/_TTREEJSONSTORE_' . $this->persistenceManager->getIdentifierByObject($this);
    }
}
