<?php
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2017
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use OaiPmhRepository\OaiPmh\AbstractXmlGenerator;
use OaiPmhRepository\OaiPmh\OaiSet\OaiSetInterface;
use OaiPmhRepository\OaiPmh\Plugin\OaiIdentifier;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Settings\SettingsInterface;

/**
 * Abstract class on which all other metadata format handlers are based.
 * Includes logic for all metadata-independent record output.
 *
 * @todo Migration to PHP 5.3 will allow the abstract getter functions to be
 *       static, as they should be
 */
abstract class AbstractMetadata extends AbstractXmlGenerator implements MetadataInterface
{
    /**
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * The class used to create the set data (spec, name and description).
     *
     * @var OaiSetInterface
     */
    protected $oaiSet;

    public function setSettings(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    public function setOaiSet(OaiSetInterface $oaiSet)
    {
        $this->oaiSet = $oaiSet;
    }

    public function getOaiSet()
    {
        return $this->oaiSet;
    }

    public function declareMetadataFormat(DOMElement $parent)
    {
        $elements = [
            'metadataPrefix' => $this->getMetadataPrefix(),
            'schema' => $this->getMetadataSchema(),
            'metadataNamespace' => $this->getMetadataNamespace(),
        ];
        $this->createElementWithChildren($parent, 'metadataFormat', $elements);
    }

    public function appendRecord(DOMElement $parent, ItemRepresentation $item)
    {
        $document = $parent->ownerDocument;
        $record = $document->createElement('record');
        $parent->appendChild($record);
        $this->appendHeader($record, $item);

        $metadata = $document->createElement('metadata');
        $record->appendChild($metadata);
        $this->appendMetadata($metadata, $item);
    }

    public function appendHeader(DOMElement $parent, ItemRepresentation $item)
    {
        $headerData['identifier'] = OaiIdentifier::itemToOaiId($item->id());

        $datestamp = $item->modified();
        if (!$datestamp) {
            $datestamp = $item->created();
        }
        $dateFormat = \OaiPmhRepository\OaiPmh\Plugin\Date::OAI_DATE_FORMAT;
        $headerData['datestamp'] = $datestamp->format($dateFormat);

        $header = $this->createElementWithChildren($parent, 'header', $headerData);
        $setSpecs = $this->oaiSet->listSetSpecs($item);
        foreach ($setSpecs as $setSpec) {
            $this->appendNewElement($header, 'setSpec', $setSpec);
        }
    }

    abstract public function appendMetadata(DOMElement $parent, ItemRepresentation $item);

    abstract public function getMetadataPrefix();

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    abstract public function getMetadataSchema();

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    abstract public function getMetadataNamespace();
}