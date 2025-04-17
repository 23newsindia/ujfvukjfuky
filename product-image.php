<?php
/**
 * Custom Product image
 *
 * @author  NasaTheme
 * @package Elessi-theme/WooCommerce
 * @version 7.8.0
 */
if (!defined('ABSPATH')) :
    exit; // Exit if accessed directly
endif;

global $product, $nasa_opt;

/**
 * Get optimized product image data
 */
function get_optimized_product_image_data($image_id, $image_size, $position = 1) {
    $image_data = wp_get_attachment_image_src($image_id, $image_size);
    $image_meta = wp_get_attachment_metadata($image_id);
    
    // Determine if we should eager load based on position
    $is_mobile = wp_is_mobile();
    $eager_load_threshold = $is_mobile ? 2 : 4;
    $should_eager_load = $position <= $eager_load_threshold;
    
    return [
        'url' => $image_data ? $image_data[0] : '',
        'width' => $image_data ? $image_data[1] : '',
        'height' => $image_data ? $image_data[2] : '',
        'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
        'loading' => $should_eager_load ? 'eager' : 'lazy',
        'lazy_class' => $should_eager_load ? '' : 'nasa-lazy',
        'fetchpriority' => $position === 1 ? 'high' : 'auto',
        'original_width' => $image_meta ? $image_meta['width'] : '',
        'original_height' => $image_meta ? $image_meta['height'] : ''
    ];
}

$product_id = $product->get_id();
$post_thumbnail_id = $product->get_image_id();
$attachment_ids = $product->get_gallery_image_ids();

$attach_video_id = elessi_get_product_meta_value($product_id, '_product_video_upload');
$video_url = $attach_video_id ? wp_get_attachment_url($attach_video_id) : false;
$attach_video_poster_id = elessi_get_product_meta_value($product_id, '_product_video_poster_upload');
$video_title = $attach_video_id ? get_the_title($attach_video_id) : '';

$image_size = apply_filters('woocommerce_gallery_image_size', 'woocommerce_single');
$full_size = apply_filters('woocommerce_gallery_full_size', apply_filters('woocommerce_product_thumbnails_large_size', 'full'));

// Get main image data
$main_image = false;
if ($post_thumbnail_id) {
    $main_image = get_optimized_product_image_data($post_thumbnail_id, $image_size, 1);
    $main_image_full = wp_get_attachment_image_src($post_thumbnail_id, $full_size);
    $main_image['full_url'] = isset($main_image_full[0]) ? $main_image_full[0] : $main_image['url'];
}

$video_poster_url = $attach_video_poster_id ? wp_get_attachment_url($attach_video_poster_id) : ($main_image ? $main_image['full_url'] : '');

$attachment_count = count($attachment_ids);

$slideHoz = false;
if (isset($nasa_opt['product_detail_layout']) && $nasa_opt['product_detail_layout'] === 'classic' && isset($nasa_opt['product_thumbs_style']) && $nasa_opt['product_thumbs_style'] === 'hoz') :
    $slideHoz = true; 
endif;

if (isset($nasa_opt['product_detail_layout']) && in_array($nasa_opt['product_detail_layout'], array('modern-1'))) :
    $slideHoz = true;
endif;

$imageMobilePadding = 'mobile-padding-left-5 mobile-padding-right-5';
if (isset($nasa_opt['product_detail_layout']) && $nasa_opt['product_detail_layout'] == 'new' && isset($nasa_opt['product_image_style']) && $nasa_opt['product_image_style'] == 'scroll') :
    $imageMobilePadding = 'mobile-padding-left-0 mobile-padding-right-0 nasa-flex align-start';
endif;

$class_main_imgs = 'main-images nasa-single-product-main-image nasa-main-image-default';

$class_wrapimg = 'row nasa-mobile-row woocommerce-product-gallery__wrapper';

$show_thumbnail = true;
if (isset($nasa_opt['product_detail_layout']) && in_array($nasa_opt['product_detail_layout'], array('full'))) :
    $show_thumbnail = false;
    $class_wrapimg = 'nasa-row nasa-mobile-row woocommerce-product-gallery__wrapper nasa-columns-padding-0';
    $imageMobilePadding = 'mobile-padding-left-0 mobile-padding-right-0';
    
    if (isset($nasa_opt['half_full_slide']) && $nasa_opt['half_full_slide']) :
        $class_main_imgs .= ' no-easyzoom';
    endif;
endif;

$sliders_arrow = isset($nasa_opt['product_slide_arrows']) && $nasa_opt['product_slide_arrows'] && isset($nasa_opt['product_image_style']) && $nasa_opt['product_image_style'] === 'slide' ? true : false;

$in_mobile = isset($nasa_opt['nasa_in_mobile']) && $nasa_opt['nasa_in_mobile'] ? true : false;
$mobile_app = $in_mobile && isset($nasa_opt['mobile_layout']) && $nasa_opt['mobile_layout'] == 'app' ? true : false;

$wrapper_classes = apply_filters(
    'woocommerce_single_product_image_gallery_classes',
    array(
        'woocommerce-product-gallery',
        'woocommerce-product-gallery--' . ($post_thumbnail_id ? 'with-images' : 'without-images'),
        'images',
    )
);
?>






<div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $wrapper_classes))); ?>">
    <div class="<?php echo esc_attr($class_wrapimg); ?>" 
     role="region" 
     aria-label="<?php esc_attr_e('Product image gallery', 'elessi-theme'); ?>"
     aria-roledescription="carousel"
     aria-live="polite">
    <div class="large-12 columns <?php echo $imageMobilePadding; ?>">
            
            
             <!-- Add your custom badge here -->
            <?php 
            // Get matching rule for this product (same logic as in your product-content)
            global $wpdb;
            $matching_rule = null;
            $rules = CTD_DB::get_all_rules();
            $product_cat_ids = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
            
            // Get parent categories
            foreach ($product_cat_ids as $cat_id) {
                $ancestors = get_ancestors($cat_id, 'product_cat');
                $product_cat_ids = array_merge($product_cat_ids, $ancestors);
            }
            $product_cat_ids = array_unique($product_cat_ids);
            
            foreach ($rules as $rule) {
                $rule_categories = json_decode($rule->categories, true);
                $excluded_products = json_decode($rule->excluded_products, true) ?: [];
                
                if (in_array($product_id, $excluded_products)) {
                    continue;
                }
                
                if (array_intersect($product_cat_ids, $rule_categories)) {
                    $matching_rule = $rule;
                    break;
                }
            }
            
            if ($matching_rule) : ?>
                <div class="top-badge"><?php echo esc_html($matching_rule->name); ?></div>
            <?php endif; ?>
            
            
            
        
            
            <div class="nasa-main-wrap rtl-left<?php echo $slideHoz ? ' nasa-thumbnail-hoz' : ''; ?>">
                <div class="product-images-slider images-popups-gallery">
                    <div class="nasa-main-image-default-wrap">
                        <?php if ($mobile_app && $attachment_count) : ?>
                            <div class="ns-img-count nasa-flex jc fs-13">
                                <span class="ns-img-now">1</span>/<span class="ns-img-total">
                                    <?php echo $video_url ? $attachment_count + 2 : $attachment_count + 1; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($sliders_arrow) : ?>
                            <div class="nasa-single-slider-arrows">
                                <a class="nasa-single-arrow nasa-disabled" data-action="prev" style="display: none;" href="javascript:void(0);" rel="nofollow">
                                    <svg width="42" height="42" viewBox="0 0 32 32" fill="currentColor"><path d="M12.792 15.233l-0.754 0.754 6.035 6.035 0.754-0.754-5.281-5.281 5.256-5.256-0.754-0.754-3.013 3.013z"/></svg>
                                </a>
                                <a class="nasa-single-arrow" data-action="next" style="display: none;" href="javascript:void(0);" rel="nofollow">
                                    <svg width="42" height="42" viewBox="0 0 32 32" fill="currentColor"><path d="M19.159 16.767l0.754-0.754-6.035-6.035-0.754 0.754 5.281 5.281-5.256 5.256 0.754 0.754 3.013-3.013z"/></svg>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="<?php echo esc_attr($class_main_imgs); ?>">
                            <?php if ($main_image) : ?>
                           
							
							<!-- Main Product Image -->
<!-- Main Product Image -->
<div class="item-wrap first" 
     role="group" 
     aria-roledescription="slide" 
     aria-hidden="false"
     tabindex="0">
    <div class="nasa-item-main-image-wrap" data-key="0">
        <div class="vanilla-zoom first" data-zoom-direction="center">
            <a href="<?php echo esc_url($main_image['full_url']); ?>" 
               class="woocommerce-main-image product-image woocommerce-product-gallery__image"
               title="<?php echo esc_attr($main_image['alt']); ?>"
               data-large-image="<?php echo esc_url($main_image['full_url']); ?>">
                <img src="<?php echo esc_url($main_image['url']); ?>"
                     data-srcset="<?php echo esc_url($main_image['url']); ?> 480w,
                                  <?php echo esc_url($main_image['full_url']); ?> 1024w"
                     data-sizes="(max-width: 480px) 100vw, (max-width: 768px) 80vw, 60vw"
                     width="<?php echo esc_attr($main_image['width']); ?>"
                     height="<?php echo esc_attr($main_image['height']); ?>"
                     alt="<?php echo esc_attr($main_image['alt']); ?>"
                     class="wp-post-image lazyload <?php echo esc_attr($main_image['lazy_class']); ?>"
                     loading="<?php echo esc_attr($main_image['loading']); ?>"
                     fetchpriority="<?php echo esc_attr($main_image['fetchpriority']); ?>"
                     data-large-image="<?php echo esc_url($main_image['full_url']); ?>"
                     data-large-image-width="<?php echo esc_attr($main_image['original_width']); ?>"
                     data-large-image-height="<?php echo esc_attr($main_image['original_height']); ?>" />
            </a>
        </div>
    </div>
</div>
							
							
                            <?php else : ?>
                                <div class="woocommerce-product-gallery__image--placeholder">
                                    <img src="<?php echo esc_url(wc_placeholder_img_src('woocommerce_single')); ?>"
                                         alt="<?php esc_attr_e('Awaiting product image', 'elessi-theme'); ?>"
                                         class="wp-post-image" />
                                </div>
                            <?php endif; ?>

                            <?php
                            if ($attachment_count > 0) :
                                $position = 2;
                                foreach ($attachment_ids as $attachment_id) :
                                    $image_data = get_optimized_product_image_data($attachment_id, $image_size, $position);
                                    $image_full = wp_get_attachment_image_src($attachment_id, $full_size);
                                    ?>
                                 
							
							
							<div class="item-wrap">
    <div class="nasa-item-main-image-wrap" data-key="<?php echo (int) $position - 1; ?>">
        <div class="vanilla-zoom" data-zoom-direction="top">
            <a href="<?php echo esc_url($image_full[0]); ?>"
               class="woocommerce-additional-image product-image"
               title="<?php echo esc_attr($image_data['alt']); ?>"
               data-large-image="<?php echo esc_url($image_full[0]); ?>">
                <img src="<?php echo esc_url($image_data['url']); ?>"
                     width="<?php echo esc_attr($image_data['width']); ?>"
                     height="<?php echo esc_attr($image_data['height']); ?>"
                     alt="<?php echo esc_attr($image_data['alt']); ?>"
                     class="<?php echo esc_attr($image_data['lazy_class']); ?>"
                     loading="<?php echo esc_attr($image_data['loading']); ?>"
                     fetchpriority="<?php echo esc_attr($image_data['fetchpriority']); ?>"
                     data-src="<?php echo esc_url($image_data['url']); ?>"
                     data-large-image="<?php echo esc_url($image_full[0]); ?>"
                     data-large-image-width="<?php echo esc_attr($image_data['original_width']); ?>"
                     data-large-image-height="<?php echo esc_attr($image_data['original_height']); ?>" />
            </a>
        </div>
    </div>
</div>
                                    <?php
                                    $position++;
                                endforeach;
                            endif;
                            ?>

                            <?php if ($video_url) : ?>
                                <div class="item-wrap">
                                    <div class="nasa-item-main-video-wrap nasa-item-main-image-wrap" data-key="<?php echo (int) $position - 1; ?>">
                                        <?php $video_poster_url = $video_poster_url ? $video_poster_url : wc_placeholder_img_src(); ?>
                                        <?php echo do_shortcode('[video src="' . esc_url($video_url) . '" preload="auto" poster="' . $video_poster_url . '"]'); ?>
                                        <img class="ns-video-size" 
                                             src="<?php echo esc_url($video_poster_url); ?>" 
                                             alt="<?php echo esc_attr($video_title); ?>"
                                             loading="lazy" />
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="product-image-btn">
                        <?php do_action('nasa_single_buttons'); ?>
                    </div>
                </div>
            </div>
            
            <?php if ($show_thumbnail && $slideHoz) : ?>
                <div class="nasa-thumb-wrap nasa-thumbnail-hoz">
                    <?php do_action('woocommerce_product_thumbnails'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Define a function to initialize the gallery
    function initGallery() {
        if (typeof VanillaZoom === 'undefined') {
            console.log('Loading VanillaZoom script...');
            const script = document.createElement('script');
            script.src = '<?php echo esc_url(get_template_directory_uri() . '/template-parts/gallery.js'); ?>';
            script.onload = function() {
                console.log('VanillaZoom script loaded, initializing...');
                if (typeof initializeZoom === 'function') {
                    initializeZoom();
                }
            };
            document.head.appendChild(script);
        } else if (typeof initializeZoom === 'function') {
            console.log('VanillaZoom already loaded, initializing...');
            initializeZoom();
        }
    }
    
    // Initialize gallery
    initGallery();
    
    // Handle product gallery navigation
    const mainImages = document.querySelector('.nasa-main-image-default');
    const prevArrow = document.querySelector('.nasa-single-arrow[data-action="prev"]');
    const nextArrow = document.querySelector('.nasa-single-arrow[data-action="next"]');
    
    if (mainImages && prevArrow && nextArrow) {
        const items = mainImages.querySelectorAll('.item-wrap');
        let currentIndex = 0;
        
        // Touch handling variables
        let touchStartX = 0;
        let touchEndX = 0;
        const minSwipeDistance = 50; // Minimum distance for a swipe
        let isSwiping = false;
        
        // Update counter if it exists
        const updateCounter = function() {
            const counter = document.querySelector('.ns-img-now');
            if (counter) {
                counter.textContent = currentIndex + 1;
            }
        };
        
        // Update arrows state
        const updateArrows = function() {
            prevArrow.classList.toggle('nasa-disabled', currentIndex === 0);
            nextArrow.classList.toggle('nasa-disabled', currentIndex === items.length - 1);
        };
        
        // Replace the existing showSlide function with this version
// Updated showSlide function with prefetching
const showSlide = function(index) {
  // Handle looping
  if (index >= items.length) {
    index = 0;
  } else if (index < 0) {
    index = items.length - 1;
  }
  
  // Smooth transitions
  items.forEach((item, i) => {
    item.style.transform = i === index ? 
      'translateX(0) scale(1)' : 
      `translateX(${(i < index ? '-' : '')}100%) scale(0.97)`;
    item.style.opacity = i === index ? 1 : 0;
    item.style.pointerEvents = i === index ? 'auto' : 'none';
    item.style.transition = 'transform 0.4s cubic-bezier(0.22, 0.61, 0.36, 1), opacity 0.3s ease';
  });
  
  currentIndex = index;
  updateArrows();
  updateCounter();
  
  // Preload adjacent images
  prefetchAdjacent();
  
  // Reinitialize zoom for active slide
  const activeSlide = items[index];
  const vanillaZooms = activeSlide.querySelectorAll('.vanilla-zoom');
  vanillaZooms.forEach(zoomElement => {
    if (zoomElement.vanillaZoomInstance) {
      zoomElement.vanillaZoomInstance.onLeave();
      delete zoomElement.vanillaZoomInstance;
    }
    zoomElement.vanillaZoomInstance = new VanillaZoom(zoomElement);
  });
};



 // Helper functions
        function updateCounter() {
            const counter = document.querySelector('.ns-img-now');
            if (counter) counter.textContent = currentIndex + 1;
        }
        
        function updateArrows() {
            prevArrow.classList.toggle('nasa-disabled', currentIndex === 0);
            nextArrow.classList.toggle('nasa-disabled', currentIndex === items.length - 1);
        }
        
        // Initialize
        showSlide(0);
        
        // Navigation event handlers
        prevArrow.addEventListener('click', () => showSlide(currentIndex - 1));
        nextArrow.addEventListener('click', () => showSlide(currentIndex + 1));




    
    // Add this right after showSlide function
function prefetchAdjacent() {
  const nextIndex = (currentIndex + 1) % items.length;
  const prevIndex = (currentIndex - 1 + items.length) % items.length;
  
  [nextIndex, prevIndex].forEach(index => {
    const img = new Image();
    img.src = items[index].querySelector('img').dataset.largeImage;
  });
}


        // Handle touch events for mobile
        mainImages.addEventListener('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
            isSwiping = false;
        }, { passive: true });

        mainImages.addEventListener('touchmove', function(e) {
            if (e.touches.length > 1) return; // Ignore multi-touch
            
            touchEndX = e.touches[0].clientX;
            const deltaX = touchEndX - touchStartX;
            
            // If horizontal movement is greater than vertical, prevent scrolling
            if (Math.abs(deltaX) > 10) {
                e.preventDefault();
                isSwiping = true;
                
                // Add visual feedback during swipe
                const translateX = deltaX + 'px';
                items[currentIndex].style.transform = `translateX(${translateX})`;
                items[currentIndex].style.transition = 'none';
            }
        }, { passive: false });

        // Update the touchend event handler to this version
// Improved touch handling constants
const TOUCH_SENSITIVITY = 0.3; // px/ms
const MIN_SWIPE_DURATION = 100; // ms

mainImages.addEventListener('touchend', function(e) {
  if (!isSwiping) return;
  
  const deltaTime = Date.now() - touchStartTime;
  const velocity = Math.abs(deltaX) / deltaTime;
  const isValidSwipe = velocity > TOUCH_SENSITIVITY && deltaTime > MIN_SWIPE_DURATION;

  // Reset transition immediately
  items[currentIndex].style.transition = 'transform 0.3s ease-out';
  items[currentIndex].style.transform = '';

  if (isValidSwipe) {
    if (deltaX > 0) showSlide(currentIndex - 1);
    else showSlide(currentIndex + 1);
  }
  
  isSwiping = false;
});



        
        // Initialize with first slide
        showSlide(0);
        
        // Add click handlers for arrows
        prevArrow.addEventListener('click', function() {
    showSlide(currentIndex - 1);
});
        
        nextArrow.addEventListener('click', function() {
    showSlide(currentIndex + 1);
});
    }
});

// Also initialize on window load to ensure all images are loaded
window.addEventListener('load', function() {
    if (typeof initializeZoom === 'function') {
        console.log('Window loaded, reinitializing zoom...');
        initializeZoom();
    }
});



// Lazy loading with Intersection Observer
if ('IntersectionObserver' in window) {
  const lazyImages = document.querySelectorAll('.nasa-lazy');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;
        img.classList.remove('nasa-lazy');
        observer.unobserve(img);
      }
    });
  }, { 
    rootMargin: '200px 0px',
    threshold: 0.01
  });

  lazyImages.forEach(img => observer.observe(img));
}


    
</script>
