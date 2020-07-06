Feature: Create an element, with slug or not stored into a the dbms server via an HTTP request

  Scenario: Create a type
    Given I have DI With Symfony initialized
    And a templating engine
    When Symfony will receive the POST request "https://foo.com/admin/type/new" with "type%5Bname%5D=foo&type%5Btemplate%5D=bar"
    Then The client must accept a response
    And An object "Type" must be persisted
    And It is redirect to "/admin/type/edit/[a-zA-Z0-9]+"
    When the client follows the redirection
    And I should get in the form "foo:bar,bar:foo"

  Scenario: Create a content
    Given I have DI With Symfony initialized
    And a templating engine
    When Symfony will receive the POST request "https://foo.com/admin/content/new" with "foo:bar,bar:foo"
    Then The client must accept a response
    And An object "Content" must be persisted
    And It is redirect to "/admin/type/content/[a-zA-Z0-9]+"
    When the client follows the redirection
    And I should get in the form "foo:bar,bar:foo"

  Scenario: Create an item
    Given I have DI With Symfony initialized
    And a templating engine
    When Symfony will receive the POST request "https://foo.com/admin/item/new" with "foo:bar,bar:foo"
    Then The client must accept a response
    And An object "Item" must be persisted
    And It is redirect to "/admin/type/item/[a-zA-Z0-9]+"
    When the client follows the redirection
    And I should get in the form "foo:bar,bar:foo"