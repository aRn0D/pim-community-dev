@javascript
Feature: Create product through CSV import
  In order to import product model
  As a catalog manager
  I need to be able to import product models with valid data

  Background:
    Given the "catalog_modeling" catalog configuration
    And I am logged in as "Julia"

  Scenario: Skip a root product model if a code and a family variant are not defined
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;;master_men;Spring2017;description;Blazers_1654;100 EUR;;;;;;;
      ;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;;;;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then I should see the text "skipped 2"
    And I should see the text "The product model code must not be empty"
    And I should see the text "Property \"family_variant\" expects a valid family variant code. The family variant does not exist, \"\" given"

  Scenario: Skip a product model if a code, a parent and a family variant are not defined
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;;;;;;;
      ;code-001;clothing_color_size;master_men_blazers;;;;;blue;Blazers;composition;;;;
      code-003;code-001;;master_men_blazers;;;;;blue;Blazers;composition;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then I should see the text "created 1"
    Then I should see the text "skipped 2"
    And I should see the text "The product model code must not be empty"
    And I should see the text "Property \"family_variant\" expects a valid family variant code. The family variant does not exist, \"\" given"

  Scenario: Skip a product model if the parent does not exist or is not a root product model
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;;;;;;;
      code-002;code-001;clothing_color_size;master_men_blazers;;;;;blue;Blazers;composition;;;;
      code-003;code-002;clothing_color_size;master_men_blazers;;;;;blue;Blazers;composition;;;;
      code-004;code-005;clothing_color_size;master_men_blazers;;;;;blue;Blazers;composition;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then I should see the text "created 2"
    Then I should see the text "skipped 2"
    And I should see the text "Property \"parent\" expects a valid parent code. The product model does not exist, \"code-005\" given"
    And I should see the text "The sub product model parent must be a root product model"

  Scenario: Skip a product model saving if its parent is the last product model in the tree (it should be a product variant instead).
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;sku;eu_shoes_size
      code-001;;shoes_size;master_men;;
      code-002;code-001;shoes_size;master_men;sku;42
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then I should see the text "created 1"
    And I should see the text "skipped 1"
    And I should see the text "The sub product model parent must be a root product model"

  Scenario: Skip the products sub-model if variant axes are empty
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;;;;;;;
      code-002;code-001;clothing_color_size;master_men_blazers;;;;;;Blazers;composition;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then I should see the text "created 1"
    Then I should see the text "skipped 1"
    And I should see the text "Attribute \"color\" cannot be empty, as it is defined as an axis for this entity: Pim\Component\Catalog\Model\ProductModel"

  Scenario: Only the attributes with values defined as "common attributes" in the family variant are updated.
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;blue;Blazers;composition;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then the product model "code-001" should not have the following values "composition, name-en_US, color"

  @skip
  Scenario: Only the attributes with values defined as variant attributes level 1 in the family variant are updated.
    Given the following CSV file to import:
      """
      code;parent;family_variant;categories;collection;description-en_US-ecommerce;erp_name-en_US;price;color;variation_name-en_US;composition;size;EAN;sku;weight
      code-001;;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;blue;Blazers;composition;;;;
      code-002;code-001;clothing_color_size;master_men;Spring2017;description;Blazers_1654;100 EUR;blue;Blazers;composition;;;;
      """
    And the following job "csv_catalog_modeling_product_model_import" configuration:
      | filePath | %file to import% |
    When I am on the "csv_catalog_modeling_product_model_import" import job page
    And I launch the import job
    And I wait for the "csv_catalog_modeling_product_model_import" job to finish
    Then the product model "code-002" should not have the following values "collection, description-en_US-ecommerce, erp_name-en_US, price"