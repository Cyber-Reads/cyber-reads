document.addEventListener('DOMContentLoaded', () => {
    
    loadCategories(); 
    
    loadBooks(); 

    
    const applyFilterButton = document.getElementById('applyFilterButton');
    const clearFilterButton = document.getElementById('clearFilterButton');

    if ( applyFilterButton ) {
        applyFilterButton.addEventListener('click', () => {
            const selectedCategories = getSelectedCategories();
            loadBooks(selectedCategories);

        }) ;
 
    }

    if (clearFilterButton ) {
        clearFilterButton.addEventListener('click', () => {
            document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;

            });


            loadBooks(); 
        });

    }

});



function loadCategories() {
    const categoryContainer = document.getElementById('categoryCheckboxes');
    if (!categoryContainer) return;

    fetch( 'get_categories.php' )
        .then(response => response.json())
        .then(categories => {
            categoryContainer.innerHTML = '';
            
            
            categories.forEach(category => {
                const label = document.createElement('label');
                label.className = 'flex items-center cursor-pointer';
                label.innerHTML = `
                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" value="${category.categoryid}"> 
                        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-black">${category.category}</span>
                        `;

                categoryContainer.appendChild(label);


            });
        })


        .catch(error => console.error('Error loading categories:', error));
}




function getSelectedCategories() {

    const selected = [];

    document.querySelectorAll('#categoryCheckboxes input[type="checkbox"]:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });


    return selected;
}


function loadBooks(category_ids = []) {

    const bookGrid = document.getElementById('bookCardGrid');

    if (!bookGrid) return;

    bookGrid.innerHTML = '<p class="text-purple-800 text-center col-span-full">Loading books...</p>';

    fetch('filter_books.php', {

        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_ids: category_ids })
    
    })
    
    .then(response => response.json())
    .then(books => {
    
        bookGrid.innerHTML = '';

        if (books.length > 0) {
            books.forEach(book => {
                const authorText = book.authors ? `by ${book.authors}` : 'Unknown Author';
                
                const bookCard = 
                `
                <div class = "group relative w-60 shadow-sm hover:shadow-xl hover:shadow-purple-200/50 hover:-translate-y-1 transition-all duration-300 ease-in-out  bg-white rounded-2xl border border-slate-200 p-3 flex flex-col gap-3  mx-auto">
    
                    <div  class = "w-full h-64 overflow-hidden shadow-inner bg-slate-100 rounded-xl  shadow-slate-200/50 ">
                        <img src = "${book.cover}" alt="${book.title} Cover" class = "w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    
                    </div >
    
                    <div class="flex flex-col flex-grow text-center mt-2">
                        <h3  class = "  text-slate-800 line-clamp-2 font-bold text-lg" title="${book.title}">${book.title}</h3>
                        <p  class = " mt-1 line-clamp-1 text-sm text-slate-500  ">${authorText}</p>

                    </div>
    
                    <a href = "bookRead.html?id=${book.bookid}" class = "w-full mt-2">

                        <button class=" py-2.5 px-4 w-full bg-black text-white font-bold  rounded-lg shadow-md  focus:ring-purple-500 focus:ring-opacity-75 transition-all duration-300 hover:bg-purple-950 focus:outline-none focus:ring-2">
                            Read Now
                        </button>
                    </a>

                </div>
                `;
                bookGrid.innerHTML += bookCard;
            });



        }
        
        else {
            bookGrid.innerHTML = '<p class="text-purple-800 text-center col-span-full">No books found for the selected criteria.</p>';
        
        }

    })


    
    .catch(error => {
        console.error ( 'Error loading books:', error);
        
        bookGrid.innerHTML = '<p class = "text-purple-700 text-center col-span-full" >Failed to load books. </p>';
    });


}


