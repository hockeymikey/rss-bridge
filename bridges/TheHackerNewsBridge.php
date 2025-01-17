<?php

class TheHackerNewsBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'The Hacker News Bridge';
    const URI = 'https://thehackernews.com/';
    const DESCRIPTION = 'Cyber Security, Hacking, Technology News.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $limit = 0;

        foreach ($html->find('div.body-post') as $element) {
            if ($limit < 5) {
                $article_url = $element->find('a.story-link', 0)->href;
                $article_author = trim($element->find('i.icon-user', 0)->parent()->plaintext);
                $article_author = str_replace('&#59396;', '', $article_author);
                $article_title = $element->find('h2.home-title', 0)->plaintext;

                $article_timestamp = time();
                //Date without time
                $calendar = $element->find('i.icon-calendar', 0);
                if ($calendar) {
                    $article_timestamp = strtotime(
                        extractFromDelimiters(
                            $calendar->parent()->outertext,
                            '</i>',
                            '<span>'
                        )
                    );
                }

                //Article thumbnail in lazy-loading image
                if (is_object($element->find('img[data-echo]', 0))) {
                    $article_thumbnail = [
                        extractFromDelimiters(
                            $element->find('img[data-echo]', 0)->outertext,
                            "data-echo='",
                            "'"
                        )
                    ];
                } else {
                    $article_thumbnail = [];
                }

                $article = getSimpleHTMLDOMCached($article_url);
                if ($article) {
                    //Article body
                    $var = $article->find('div.articlebody', 0);
                    if ($var) {
                        $contents = $var->innertext;
                        $contents = stripRecursiveHtmlSection($contents, 'div', '<div class="ad_');
                        $contents = stripWithDelimiters($contents, 'id="google_ads', '</iframe>');
                        $contents = stripWithDelimiters($contents, '<script', '</script>');
                    }
                    //Date with time
                    if (is_object($article->find('meta[itemprop=dateModified]', 0))) {
                        $article_timestamp = strtotime(
                            extractFromDelimiters(
                                $article->find('meta[itemprop=dateModified]', 0)->outertext,
                                "content='",
                                "'"
                            )
                        );
                    }
                } else {
                    $contents = 'Could not request TheHackerNews: ' . $article_url;
                }

                $item = [];
                $item['uri'] = $article_url;
                $item['title'] = $article_title;
                $item['author'] = $article_author;
                $item['enclosures'] = $article_thumbnail;
                $item['timestamp'] = $article_timestamp;
                $item['content'] = trim($contents ?? '');
                $this->items[] = $item;
                $limit++;
            }
        }
    }
}
