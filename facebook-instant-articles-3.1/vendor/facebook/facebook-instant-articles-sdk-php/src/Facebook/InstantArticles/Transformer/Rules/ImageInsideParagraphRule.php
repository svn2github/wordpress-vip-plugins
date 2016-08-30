<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Transformer\Rules;

use Facebook\InstantArticles\Elements\Image;
use Facebook\InstantArticles\Elements\TextContainer;
use Facebook\InstantArticles\Elements\Paragraph;
use Facebook\InstantArticles\Transformer\Warnings\InvalidSelector;
use Facebook\InstantArticles\Transformer\Warnings\NoRootInstantArticleFoundWarning;

class ImageInsideParagraphRule extends ConfigurationSelectorRule
{
    const PROPERTY_IMAGE_URL = 'image.url';
    const PROPERTY_LIKE = 'image.like';
    const PROPERTY_COMMENTS = 'image.comments';

    public function getContextClass()
    {
        return Paragraph::getClassName();
    }

    public static function create()
    {
        return new ImageInsideParagraphRule();
    }

    public static function createFrom($configuration)
    {
        $image_rule = self::create();
        $image_rule->withSelector($configuration['selector']);

        $image_rule->withProperties(
            [
                self::PROPERTY_IMAGE_URL,
                self::PROPERTY_LIKE,
                self::PROPERTY_COMMENTS
            ],
            $configuration
        );

        return $image_rule;
    }

    public function apply($transformer, $context, $node)
    {
        $image = Image::create();

        // Builds the image
        $url = $this->getProperty(self::PROPERTY_IMAGE_URL, $node);
        if ($url) {
            $image->withURL($url);
            $instant_article = $transformer->getInstantArticle();
            if ($instant_article) {
                $instant_article->addChild($image);
                $context->disableEmptyValidation();
                $context = Paragraph::create();
                $context->disableEmptyValidation();
                $instant_article->addChild($context);
            } else {
                $transformer->addWarning(
                    // This new error message should be something like:
                    // Could not transform Image, as no root InstantArticle was provided.
                    new NoRootInstantArticleFoundWarning(null, $node)
                );
            }
        } else {
            $transformer->addWarning(
                new InvalidSelector(
                    self::PROPERTY_IMAGE_URL,
                    $instant_article,
                    $node,
                    $this
                )
            );
        }

        if ($this->getProperty(self::PROPERTY_LIKE, $node)) {
            $image->enableLike();
        }

        if ($this->getProperty(self::PROPERTY_COMMENTS, $node)) {
            $image->enableComments();
        }

        $suppress_warnings = $transformer->suppress_warnings;
        $transformer->suppress_warnings = true;
        $transformer->transform($image, $node);
        $transformer->suppress_warnings = $suppress_warnings;

        return $context;
    }
}
