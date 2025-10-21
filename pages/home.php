<?php
// pages/home.php
// The main landing page for the application, displaying slides and upcoming events.

// Fetch active slides from the database, ordered by their sort_order.
$slides_stmt = $mysqli->prepare("SELECT * FROM slides WHERE is_active = 1 ORDER BY sort_order ASC");
$slides_stmt->execute();
$slides_result = $slides_stmt->get_result();
$slides = $slides_result->fetch_all(MYSQLI_ASSOC);
$slides_stmt->close();

// Fetch events that are set to be visible and are not cancelled, ordered by sort_order.
$events_stmt = $mysqli->prepare("SELECT id, event_code, name, slogan, card_thumbnail_url, is_registration_open, start_date FROM events WHERE is_visible = 1 AND is_cancelled = 0 ORDER BY sort_order ASC");
$events_stmt->execute();
$events_result = $events_stmt->get_result();
$events = $events_result->fetch_all(MYSQLI_ASSOC);
$events_stmt->close();
?>

<!-- Carousel Section -->
<div id="news-carousel-section" class="mb-8">
    <?php if (!empty($slides)): ?>
    <div id="news-carousel-container" class="relative rounded-xl overflow-hidden shadow-xl">
        <div id="carousel-slides" class="flex transition-transform duration-500 ease-in-out">
            <?php foreach($slides as $slide): ?>
            <div class="w-full shrink-0 cursor-pointer" onclick="window.location.href='<?= e($slide['link_url']) ?>'">
                <div class="relative h-48 sm:h-64 bg-cover bg-center" style="background-image: url('<?= e($slide['image_url']) ?>')">
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center p-6">
                        <div class="text-white">
                            <h3 class="text-2xl font-bold"><?= e($slide['title']) ?></h3>
                            <p class="text-sm mt-1"><?= e($slide['subtitle']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Navigation Buttons -->
        <button onclick="prevSlide(event)" class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-black bg-opacity-50 p-3 rounded-full text-white hover:bg-opacity-70 transition z-10 text-lg">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button onclick="nextSlide(event)" class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-black bg-opacity-50 p-3 rounded-full text-white hover:bg-opacity-70 transition z-10 text-lg">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <!-- Dots Indicators -->
        <div id="carousel-dots" class="absolute bottom-4 left-0 right-0 flex justify-center space-x-2 z-10">
            <?php foreach($slides as $index => $_): ?>
            <span class="w-3 h-3 rounded-full bg-white bg-opacity-50 cursor-pointer transition duration-300" onclick="goToSlide(event, <?= $index ?>)"></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>


<h2 class="text-3xl font-extrabold mb-6 text-gray-800">กิจกรรมที่กำลังจะมาถึง</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (empty($events)): ?>
        <div class="col-span-1 md:col-span-2 text-center p-10 border-2 border-dashed border-gray-300 rounded-lg text-gray-500">
            <i class="fa-solid fa-calendar-times text-3xl mb-3"></i>
            <p>ยังไม่มีกิจกรรมที่กำลังจะมาถึงในขณะนี้</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="rounded-xl shadow-lg border border-gray-200 transition duration-300 hover:shadow-xl hover:border-primary cursor-pointer overflow-hidden" 
                 onclick="window.location.href='index.php?page=microsite&event_code=<?= e($event['event_code']) ?>'">
                
                <div class="h-36 bg-cover bg-center" style="background-image: url('<?= e($event['card_thumbnail_url']) ?>');"></div>
                
                <div class="p-4">
                    <h3 class="text-xl font-bold mb-1 text-primary"><?= e($event['name']) ?></h3>
                    <p class="text-gray-600 mb-3 text-sm"><?= e($event['slogan']) ?></p>
                    <div class="flex flex-col space-y-1 text-xs">
                        <span class="flex items-center text-gray-700">
                            <i class="fa-solid fa-calendar-alt w-5 mr-2"></i> วันที่: <?= date("d M Y", strtotime($event['start_date'])) ?>
                        </span>
                        <span class="flex items-center <?= $event['is_registration_open'] ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                            <i class="fa-solid fa-circle w-5 mr-2 text-xs"></i> 
                            สถานะ: <?= $event['is_registration_open'] ? 'เปิดรับสมัคร' : 'ปิดรับสมัคร' ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
let currentSlideIndex = 0;
let carouselInterval;
const slidesContainer = document.getElementById('carousel-slides');
const slides = slidesContainer ? slidesContainer.children : [];
const dots = document.querySelectorAll('#carousel-dots > span');

function updateCarouselPosition() {
    if (slides.length > 0) {
        const offset = -currentSlideIndex * 100;
        slidesContainer.style.transform = `translateX(${offset}%)`;
    }
    dots.forEach((dot, index) => {
        dot.classList.toggle('bg-opacity-100', index === currentSlideIndex);
        dot.classList.toggle('bg-opacity-50', index !== currentSlideIndex);
    });
}

function nextSlide(e) {
    if(e) e.stopPropagation();
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    updateCarouselPosition();
    resetInterval();
}

function prevSlide(e) {
    if(e) e.stopPropagation();
    currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
    updateCarouselPosition();
    resetInterval();
}

function goToSlide(e, index) {
    if(e) e.stopPropagation();
    currentSlideIndex = index;
    updateCarouselPosition();
    resetInterval();
}

function resetInterval() {
    clearInterval(carouselInterval);
    carouselInterval = setInterval(nextSlide, 5000);
}

document.addEventListener('DOMContentLoaded', () => {
    if (slides.length > 0) {
        updateCarouselPosition();
        resetInterval();
    }
});
</script>

