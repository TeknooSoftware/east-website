Feature: Update an element, with slug or not stored into a the dbms server via an HTTP request
  
  Scenario: Update a type
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Object\Type" with id "foo" and '{"name":"foo","template":"bar","blocks":[]}'
    When Symfony will receive the POST request "https://foo.com/admin/type/edit/foo" with "type%5Bname%5D=foo2&type%5Btemplate%5D=bar3"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"name":"foo2","template":"bar3","blocks":[]}'

  Scenario: Update a post without update slug
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Post" with id "foo" and '{"author":null,"title":"foo","subtitle":"bar","slug":"foo"}'
    When Symfony will receive the POST request "https://foo.com/admin/post/edit/foo" with "post%5Btitle%5D=foo2&post%5Bsubtitle%5D=bar3&post%5Bslug%5D=foo"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"author":null,"comments":[],"title":"foo2","subtitle":"bar3","slug":"foo","type":null,"parts":"{}","tags":[],"description":null}'

  Scenario: Update a post and update slug
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Post" with id "foo" and '{"author":null,"title":"foo","subtitle":"bar","slug":"foo"}'
    When Symfony will receive the POST request "https://foo.com/admin/post/edit/foo" with "post%5Btitle%5D=foo2&post%5Bsubtitle%5D=bar3"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"author":null,"comments":[],"title":"foo2","subtitle":"bar3","slug":"foo2","type":null,"parts":"{}","tags":[],"description":null}'

  Scenario: Update a post with an empty locale
    Given I have DI With Symfony initialized
    And an empty locale
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Post" with id "foo" and '{"author":null,"title":"foo","subtitle":"bar","slug":"foo"}'
    When Symfony will receive the POST request "https://foo.com/admin/post/edit/foo" with "post%5Btitle%5D=foo2&post%5Bsubtitle%5D=bar3"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"author":null,"comments":[],"title":"foo2","subtitle":"bar3","slug":"foo2","type":null,"parts":"{}","tags":[],"description":null}'

  Scenario: Update an item without update slug
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "foo" and '{"name":"foo","slug":"foo","content":null,"position":1,"location":"bar"}'
    When Symfony will receive the POST request "https://foo.com/admin/item/edit/foo" with "item%5Bname%5D=foo2&item%5Blocation%5D=bar3&item%5Bposition%5D=1&item%5Bslug%5D=foo"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"name":"foo2","slug":"foo","content":null,"position":1,"location":"bar3","hidden":false,"parent":null,"children":[]}'

  Scenario: Update an item and update slug
    Given I have DI With Symfony initialized
    And a twig templating engine
    And a object of type "Teknoo\East\Website\Doctrine\Object\Item" with id "foo" and '{"name":"foo","slug":"foo","content":null,"position":1,"location":"bar"}'
    When Symfony will receive the POST request "https://foo.com/admin/item/edit/foo" with "item%5Bname%5D=foo2&item%5Blocation%5D=bar3&item%5Bposition%5D=1"
    Then The client must accept a response
    And An object "foo" must be updated
    And I should get in the form '{"name":"foo2","slug":"foo2","content":null,"position":1,"location":"bar3","hidden":false,"parent":null,"children":[]}'