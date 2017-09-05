<?php

namespace Pim\Bundle\CatalogBundle\tests\integration\PQB;

use Akeneo\Bundle\ElasticsearchBundle\Client;
use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Catalog\Model\VariantProduct;
use Pim\Component\Catalog\Model\VariantProductInterface;

/**
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class AbstractProductAndProductModelQueryBuilderTestCase extends TestCase
{
    /** @var Client */
    protected $esProductAndProductModelClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->esProductAndProductModelClient = $this->get('akeneo_elasticsearch.client.product_and_product_model');
    }

    /**
     * @param string                     $identifier
     * @param string                     $familyVariantCode
     * @param null|ProductModelInterface $parent
     * @param array                      $data
     *
     * @return ProductModelInterface
     */
    protected function createProductModel(
        string $identifier,
        string $familyVariantCode,
        ?ProductModelInterface $parent,
        array $data
    ): ProductModelInterface {
        $productModel = $this->get('pim_catalog.factory.product_model')->create();

        $this->get('pim_catalog.updater.product_model')->update($productModel, [
            'code'           => $identifier,
            'family_variant' => $familyVariantCode,
        ]);

        if (null !== $parent) {
            $productModel->setParent($parent);
        }

        $this->updateProductModel($productModel, $data);

        return $productModel;
    }

    /**
     * @param ProductModelInterface $productModel
     * @param array                 $data
     */
    protected function updateProductModel(ProductModelInterface $productModel, array $data): void
    {
        $this->get('pim_catalog.updater.product_model')->update($productModel, $data);
        $this->get('pim_catalog.saver.product_model')->save($productModel);

        $this->esProductAndProductModelClient->refreshIndex();
    }

    /**
     * TODO: use the factory/builder of variant products when it exists
     *
     * Creates a variant product with identifier and product model parent
     *
     * @param string                $identifier
     * @param string                $familyCode
     * @param string                $familyVariantCode
     * @param ProductModelInterface $parent
     * @param array                 $data
     *
     * @return VariantProductInterface
     */
    protected function createVariantProduct(
        string $identifier,
        string $familyCode,
        string $familyVariantCode,
        ProductModelInterface $parent,
        array $data
    ): VariantProductInterface {
        $variantProduct = new VariantProduct();

        $identifierAttribute = $this->get('pim_catalog.repository.attribute')->findOneByCode('sku');

        $entityWithValuesBuilder = $this->get('pim_catalog.builder.entity_with_values');
        $entityWithValuesBuilder->addOrReplaceValue($variantProduct, $identifierAttribute, null, null, $identifier);

        $this->get('pim_catalog.updater.product')->update(
            $variantProduct,
            [
                'family' => $familyCode,
            ]
        );

        $variantProduct->setParent($parent);

        $familyVariant = $this->get('pim_catalog.repository.family_variant')->findOneByCode($familyVariantCode);
        $variantProduct->setFamilyVariant($familyVariant);

        $this->updateVariantProduct($variantProduct, $data);

        return $variantProduct;
    }

    /**
     * @param VariantProductInterface $variantProduct
     * @param array                   $data
     */
    protected function updateVariantProduct(VariantProductInterface $variantProduct, array $data): void
    {
        $this->get('pim_catalog.updater.product')->update($variantProduct, $data);
        $this->get('pim_catalog.saver.product')->save($variantProduct);

        $this->esProductAndProductModelClient->refreshIndex();
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration([Configuration::getFunctionalCatalogPath('catalog_modeling')]);
    }

    /**
     * @param array $filters
     *
     * @return CursorInterface
     */
    protected function executeFilter(array $filters): CursorInterface
    {
        $pqb = $this->get('pim_enrich.query.product_and_product_model_query_builder_from_size_factory')->create(
            [
                'default_locale' => 'en_US',
                'default_scope'  => 'ecommerce',
                'limit'          => 200, // set it big enough to have all products in one page
            ]
        );

        foreach ($filters as $filter) {
            $context = isset($filter[3]) ? $filter[3] : [];
            $pqb->addFilter($filter[0], $filter[1], $filter[2], $context);
        }

        return $pqb->execute();
    }

    /**
     * @param CursorInterface $result
     * @param array           $expected
     */
    protected function assert(CursorInterface $result, array $expected): void
    {
        $entities = [];
        foreach ($result as $entity) {
            if ($entity instanceof ProductInterface) {
                $entities[] = $entity->getIdentifier();
            }

            if ($entity instanceof ProductModelInterface) {
                $entities[] = $entity->getCode();
            }
        }

        sort($entities);
        sort($expected);

        $this->assertSame($entities, $expected);
    }
}
