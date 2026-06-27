// // "aboutButton" ID 
// const aboutButton = document.getElementById('aboutButton');

// // click event listener
// aboutButton.addEventListener('click', function() {
//     // "about.html"
//     window.location.href = "about.html";
// });

// // const readButton = document.getElementById('readButton');

// //     // බොත්තමට click event listener එකක් එකතු කරයි
// //     readButton.addEventListener('click', function() {
// //         // "bookRead.html" ගොනුවට යොමු කරයි.
// //         // bookRead.html ගොනුව වත්මන් HTML ගොනුව හා එකම ෆෝල්ඩරයේ තිබිය යුතුය.
// //         window.location.href = "D:\\webProject\\src\\frontEnd\\bookRead.html";
// // });

// // Carousel
// let currentIndex = 0;
// const track = document.getElementById('carousel-track');
// const totalBooks = 6;
// const booksPerView = 3;
// const maxIndex = totalBooks - booksPerView;

// function updateCarousel() {
//   const offset = currentIndex * (100 / booksPerView);
//   track.style.transform = `translateX(-${offset}%)`;
  
//   // Update dots
//   for (let i = 0; i <= maxIndex; i++) {
//     const dot = document.getElementById(`dot-${i}`);
//     if (dot) {
//       dot.classList.toggle('bg-gray-800', i === currentIndex);
//       dot.classList.toggle('bg-gray-400', i !== currentIndex);
//     }
//   }
// }

// function moveCarousel(direction) {
//   currentIndex += direction;
//   if (currentIndex < 0) currentIndex = maxIndex;
//   if (currentIndex > maxIndex) currentIndex = 0;
//   updateCarousel();
// }

// function goToSlide(index) {
//   currentIndex = index;
//   updateCarousel();
// }

// // Auto-play carousel every 5 seconds
// setInterval(() => {
//   moveCarousel(1);
// }, 5000);

// // Initialize carousel on page load
// window.addEventListener('DOMContentLoaded', () => {
//   updateCarousel();
// });