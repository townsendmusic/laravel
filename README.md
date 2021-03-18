## Townsend Music Laravel coding test

<h4>Stage 1</h4>
<p>Refactor the <code>sectionProducts()</code> method in <code>app/store_products.php</code> along with it's functionality into Laravel.</p>
<p>Two routes should be created <code>/products</code> and <code>/products/sectionname</code> that return all the products and then just the products for the selected section.</p>
<p>A ProductsController is in place to set these up and the they should return JSON of the same info passed by the original method</p> 
<p>Sample data can be obtained by importing the <code>laraveltest.dump</code> file into your local database.</p>
<p>The models and relationships have already been created.</p>

<h4>Stage 2</h4>
<p>Add a search (/products/search/searchterm)</p>
<p>Add caching</p>
<p>Add the ability to return only preorder products (release date in the future)</p>