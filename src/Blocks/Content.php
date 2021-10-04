<?php

namespace Prophe1\ACFBlockz\Blocks;

use WP_Block_Type_Registry;
use function App\config;
use function App\template;

/**
 * Class Blocks
 * @package App\Helpers\Gutenberg
 */
class Content
{
    /**
     * The id of the block
     *
     * @var string
     */
    private $id;

    /**
     * The default container for a block
     *
     * @var string
     */
    private $container;

    /**
     * The default containers for a blocks
     *
     * @var array
     */
    private $containers;

    /**
     * Whether or not we should disable the inner container
     *
     * @var bool
     */
    private $disable_inner_container = false;

    /**
     * The WP block type instance
     *
     * @var \WP_Block_Type|null
     */
    private $block_type;

    /**
     * @var int
     */
    private $block_index = 0;

    /**
     * Block alignment
     *
     * @var string
     */
    private $block_alignment = '';

    /**
     * Block classes
     *
     * @var array
     */
    private $block_classes = [];

    /**
     * Block styles
     *
     * @var array
     */
    private $block_styles = [];

    /**
     * The content of a block
     *
     * @var string
     */
    private $block_content;

    /**
     * The block attributes
     *
     * @var array
     */
    private $block;

    /**
     * The block inner
     * @var string
     */
    private $inner;

    /**
     * The blocks columns
     *
     * @var string
     */
    private $columns;

    /**
     * Is the block dynamic
     *
     * @var bool
     */
    private $is_dynamic = false;

    /**
     * The block type
     *
     * @var string
     */
    private $type;

    /**
     * The block slug
     *
     * @var string
     */
    private $slug;

    /**
     * The block counter
     * Used for the id
     *
     * @var int
     */
    public static $counter = 0;

    /**
     * @var array
     */
    private $containerClasses;

    /**
     * @var array
     */
    private $alignmentClasses;

    /**
     * @var boolean
     */
    private $has_background_color;

    /**
     * @var false|mixed
     */
    private $is_wrapped;

    /**
     * Blocks constructor.
     */
    public function __construct()
    {
        $this->containerClasses = apply_filters('content/containerClasses', [
            'sm' => 'inner--prose',
            'md' => 'inner--content',
            'full' => 'inner--full'
        ]);

        $this->alignmentClasses = apply_filters('content/alignmentClasses', [
            'center' => 'text-center',
            'left' => 'text-left',
            'right' => 'text-right'
        ]);

        $containers = apply_filters('content/render', [
            'default_inner' => $this->getContainerClass('sm'),
            'inner_prose' => [],
            'no_container' => [],
            'prose_default' => [],
            'no_wrap' => []
        ]);

        $this->setContainers($containers);
        $this->setContainer($this->getContainers('default_inner'));
        $this->block_type = WP_Block_Type_Registry::get_instance();
    }

    /**
     * @param $position string
     * @return string
     */
    private function getAlignmentClass($position): string
    {
        return $this->alignmentClasses[$position];
    }

    /**
     * @param $size string
     * @return string
     */
    private function getContainerClass($size): string
    {
        return $this->containerClasses[$size];
    }

    /**
     * @param string $key
     * @return array|string
     */
    public function getContainers(string $key)
    {
        return $this->containers[$key];
    }

    /**
     * @return string
     */
    public function getContainer(): string
    {
        return $this->container;
    }

    /**
     * @param  string  $container
     */
    public function setContainer(string $container): void
    {
        $this->container = $container;
    }

    /**
     * @param  string  $container
     */
    public function setContainers(array $containers): void
    {
        $this->containers = $containers;
    }

    /**
     * Checks if the block is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !trim($this->block_content) && !trim($this->block['innerHTML']) && empty($this->block['blockName']);
    }

    /**
     * Checks if the block is dynamic and sets it
     */
    private function isblockDynamic()
    {
        $this->is_dynamic = $this->block['blockName'] && null !== $this->block_type && $this->block_type->is_dynamic();
    }

    /**
     * Checks for empty paragraph tags
     *
     * @return bool
     */
    private function removeEmptyParagraphTags()
    {
        return isset($this->block['blockName']) && $this->block['blockName'] == 'core/paragraph' && trim(strip_tags($this->block['innerHTML'])) == '';
    }

    /**
     * Setup the block
     *
     * @return $this
     */
    private function setupblock()
    {
        $this->block_type = WP_Block_Type_Registry::get_instance()->get_registered($this->block['blockName']);
        $this->isblockDynamic();
        return $this;
    }

    /**
     * Set the type and slug for the block
     *
     * @return $this
     */
    private function setTypeAndSlug()
    {
        if (isset($this->block['blockName'])) {
            // Getting block type and slug
            list($type, $slug) = explode('/', $this->block['blockName']);
        } else {
            $type = 'custom';
            $slug = 'content';
        }

        $this->type = $type;
        $this->slug = $slug;
        return $this;
    }

    /**
     * Set the ID
     *
     * @return $this
     */
    private function setId()
    {
        self::$counter++;
        // Set Default Inner
        $this->id = isset($block['attrs']['section_id']) ? $this->block['attrs']['section_id'] : $this->slug . '-' . self::$counter;
        return $this;
    }

    /**
     * Overwrites the default container
     *
     * @return $this
     */
    private function setBlockContainer()
    {
        // sets inner--prose container around
        if (in_array($this->block['blockName'], $this->getContainers('inner_prose'))) {
            $this->setContainer($this->getContainerClass('sm'));
        }

        // removes inner wrapper for spacer component
        if (in_array($this->block['blockName'], $this->getContainers('no_container'))) {
            $this->setContainer('');
        }

        if ($this->inner) {
            $this->setContainer(false);
        }

        return $this;
    }

    /**
     * Sets the block alignment
     *
     * @return $this
     */
    private function setBlockAlignment()
    {
        // Set the block container to prose then allow overrides with alignment options
        if (in_array($this->block['blockName'], $this->getContainers('prose_default'), true)) {
            $this->setContainer($this->getContainerClass('sm'));
        }

        if (isset($this->block['attrs']['align'])) {

            switch ($this->block['attrs']['align']) {
                case 'full':
                    $this->setContainer($this->getContainerClass('full'));
                    break;

                case 'wide':
                    $this->setContainer($this->getContainerClass('md'));
                    break;

                case 'center':
                    $this->block_alignment = sprintf(' %s', $this->getAlignmentClass('center'));
                    break;

                case 'left':
                    $this->block_alignment = sprintf(' %s', $this->getAlignmentClass('left'));
                    break;

                case 'right':
                    $this->block_alignment = sprintf(' %s', $this->getAlignmentClass('right'));
                    break;
            }
        }

        // Force inner--prose
        if (in_array($this->block['blockName'], $this->getContainers('inner_prose'), true)) {
            $this->setContainer($this->getContainerClass('sm'));
        }

        return $this;
    }

    /**
     * Adds a background class to a block
     *
     * @return $this
     */
    private function setBackground()
    {
        // Custom blocks
        if (isset($this->block['attrs']['data']['block_background_color']) && $bg_color = $this->block['attrs']['data']['block_background_color']) {
            $tw_class = array_search($bg_color, config('theme.colors'));

            if ($tw_class) {
                $this->block_classes[] = sprintf('bg-%s py-8 lg:py-12', $tw_class);
            } else {
                $this->block_classes[] = 'py-8 lg:py-12';
                $this->block_styles[] = sprintf('background-color:%s;', $bg_color);
            }
        }

        // Core blocks
        if (isset($this->block['attrs']['backgroundColor']) && $bg_class = $this->block['attrs']['backgroundColor']) {
            $this->block_classes[] = sprintf('has-background bg-%s py-6 lg:py-8', $bg_class);
        }

        return $this;
    }

    /**
     * Adds a text class to a block
     *
     * @return $this
     */
    private function setTextColor()
    {
        // Custom blocks
        if (isset($this->block['attrs']['data']['block_text_color']) && $text_color = $this->block['attrs']['data']['block_text_color']) {
            $tw_class = array_search($text_color, config('theme.colors'));

            if ($tw_class) {
                $this->block_classes[] = sprintf('text-%s', $tw_class);
            } else {
                $this->block_styles[] = sprintf('color:%s;', $text_color);
            }
        }

        // Core blocks
        if (isset($this->block['attrs']['textColor']) && $text_class = $this->block['attrs']['textColor']) {
            $this->block_classes[] = sprintf('text-%s', $text_class);
        }

        return $this;
    }

    /**
     * Adds spacing (margin or padding) class to a block
     *
     * @return $this
     */
    private function setSpacing()
    {
        $spacing_size_top = 'none';
        $spacing_size_bottom = 'none';

        // Only custom blocks

        if (isset($this->block['attrs']['data']['block_spacing_top']) && $spacing_top = $this->block['attrs']['data']['block_spacing_top']) {
            $spacing_size_top = $spacing_top;
        }

        if (isset($this->block['attrs']['data']['block_spacing_bottom']) && $spacing_bottom = $this->block['attrs']['data']['block_spacing_bottom']) {
            $spacing_size_bottom = $spacing_bottom;
        }

        if ($this->type !== 'core' && $spacing_size_top !== 'none') {
            $this->block_classes[] = $spacing_size_top;
        }

        if ($this->type !== 'core' && $spacing_size_bottom !== 'none') {
            $this->block_classes[] = $spacing_size_bottom;
        }

        return $this;
    }

    /**
     * Adds any custom classes to our block
     *
     * @return $this
     */
    private function setBlockClasses()
    {
        if (isset($this->block['attrs']['className'])) {
            $this->block_classes[] = $this->block['attrs']['className'];
        }
        return $this;
    }

    /**
     *
     */
    private function setBlockContent()
    {
        foreach ($this->block['innerContent'] as $chunk) {
            if (is_string($chunk)) {
                $this->block_content .= $chunk;
            } else {
                $this->block_content .= self::render(
                    '',
                    $this->block['innerBlocks'][$this->block_index++],
                    $this->disable_inner_container,
                    $this->columns
                );
            }
        }

        if ($this->is_dynamic && !$this->block_content) {
            global $post;
            $global_post = $post;
            $this->block_content = $this->block_type->render($this->block['attrs'], $this->block_content);
            $post = $global_post;
        }
    }

    /**
     * @return bool
     */
    private function isWrapped()
    {
        if ($this->is_wrapped && !in_array($this->block['blockName'], $this->getContainers('no_wrap'))) {
            return true;
        }

        return false;
    }


    /**
     * @param  string  $block_content
     * @param  array  $block
     * @param  bool  $inner
     * @param  bool  $columns
     * @return bool|\Illuminate\Contracts\View\Factory|\Illuminate\View\Factory|\Illuminate\View\View
     */
    public static function render($block_content = '', array $block, $inner = false, $columns = false, $is_wrapped = false)
    {
        $content = new Content();

        $content->block_content = $block_content;
        $content->block = $block;
        $content->inner = $inner;
        $content->columns = $columns;
        $content->is_wrapped = $is_wrapped;

        if ($content->isEmpty() || $content->removeEmptyParagraphTags()) {
            return false;
        }

        $content->setupBlock()
            ->setTypeAndSlug()
            ->setId()
            ->setBlockContainer()
            ->setBlockAlignment()
            ->setBackground()
            ->setTextColor()
            ->setSpacing()
            ->setBlockClasses()
            ->setBlockContent();

        if (!$content->isWrapped()) {
            return $content->block_content;
        }

        return template(
            'blocks.block-container',
            [
                'block'     => $content->block,
                'content'   => $content->block_content,
                'container' => $content->getContainer(),
                'type'      => $content->type,
                'slug'      => str_replace(['acf-'], '', $content->slug),
                'class'     => implode(' ', $content->block_classes),
                'style'     => implode(' ', $content->block_styles),
                'align'     => $content->block_alignment,
                'ids'       => $content->id,
            ]
        );
    }
}
