Feature: Comment a published post, from the public JSON API
  As a visitor, I need to post, from the public JSON API, a comment on a published post, with a JSON body or an
  urlencoded body. After the posting, the client is redirected to the post.

  Background:
    Given I have DI With Symfony initialized for API

  Scenario: Comment a post with a JSON body
    Given a type "type1" named "article" with the blocks "body"
    And a published post "p1" with the slug "hello-world", the title "Hello World", the parts '{"body": "Hi"}' and the sanitized parts '{"body": "Hi"}'
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/post/hello-world/comment" with:
      """
      {
        "author": "Max",
        "title": "Hello",
        "content": "Nice article"
      }
      """
    Then It is redirect to "/api/v1/post/hello-world\?id=[a-zA-Z0-9]+"
    And An object "Comment" must be persisted
    When the client follows the redirection
    Then the API response status code is 200
    And the API response contains:
      """
      {
        "meta": {
          "id": "p1"
        },
        "data": {
          "title": "Hello World"
        }
      }
      """

  Scenario: Comment a post with an urlencoded body
    Given a type "type1" named "article" with the blocks "body"
    And a published post "p1" with the slug "hello-world", the title "Hello World", the parts '{"body": "Hi"}' and the sanitized parts '{"body": "Hi"}'
    When the API client sends a "POST" form request to "https://foo.com/api/v1/post/hello-world/comment" with "new_comment%5Bauthor%5D=Max&new_comment%5Btitle%5D=Hello&new_comment%5Bcontent%5D=Nice"
    Then It is redirect to "/api/v1/post/hello-world\?id=[a-zA-Z0-9]+"
    And An object "Comment" must be persisted

  Scenario: Comment a post with an invalid comment, errors not bubbled to the root form are not returned, only the post
    Given a type "type1" named "article" with the blocks "body"
    And a published post "p1" with the slug "hello-world", the title "Hello World", the parts '{"body": "Hi"}' and the sanitized parts '{"body": "Hi"}'
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/post/hello-world/comment" with:
      """
      {
        "author": "Max",
        "title": "",
        "content": ""
      }
      """
    Then the API response status code is 400
    And the API response is a JSON response
    And the API response contains:
      """
      {
        "meta": {
          "id": "p1"
        },
        "data": {
          "id": "p1",
          "title": "Hello World"
        }
      }
      """
    And there are 0 objects "Comment" persisted

  Scenario: Comment an unknown post
    When the API client sends a "POST" JSON request to "https://foo.com/api/v1/post/unknown/comment" with:
      """
      {
        "author": "Max",
        "title": "Hello",
        "content": "Nice article"
      }
      """
    Then the API response is the error 404
