/**
 * This JavaScript code enhances the behavior of a WooCommerce checkout page by dynamically updating the selection options for stores and associated areas. Here's a summary of its key functionalities:
 * 
 * 1. Initialization:
 * Waits for the DOM content to be fully loaded.
 * 
 * 2.Element Selection:
 * Selects various DOM elements representing the container for store selection (billingStoresField), 
 * a dynamically created div for store options (storeSelect), and other relevant elements.
 * 
 * 3. Options Extraction:
 * Extracts and processes option tags from the billing_city dropdown, creating an array of objects 
 * (storeObjArr) with city and store properties. Also creates an array (storeArr) containing unique store values.
 * 
 * 4. Create Store Options:
 * Dynamically generates radio input elements for each store in the storeArr, 
 * creating a user-friendly selection interface.
 * Appends the generated radio inputs and labels to a dynamically created storeSelect div, 
 * which is then appended to the billingStoresField container.
 * 
 * 5. Update Area Options:
 * Clears and updates the options in the billingCity dropdown based on the selected store.
 * 
 * 6. Event Listener:
 * Listens for changes in the selected store and triggers the update of associated areas (cities) in the billingCity dropdown.
 * In summary, the code creates a more interactive and user-friendly store and area selection experience 
 * during the WooCommerce checkout process, dynamically updating options based on user input.
 */

document.addEventListener("DOMContentLoaded", () => {
    // billingStoresField wraps the billing_stores select and drop down
    const billingStoresField = document.getElementById("billing_stores_field")
    const storeSelect = document.createElement("div")
    storeSelect.id = 'billing_stores'
    const stores = document.getElementById("select2-billing_stores-results")
    const cities = document.getElementById("select2-billing_city-results")
    const placeOrderBtn = document.getElementById("place_order")
    
    const billingCity = document.querySelector("#billing_city")
    
    const billingCitiesOptions = document.querySelectorAll("#billing_city option")
    
    // Extract option tags from stores, split, 
    // grab stores and areas.
    const storeObjArr = []
    const storeArr = []
    billingCitiesOptions.forEach((option)=> {
        let split = option.value.split('--')
        if(split[1] && !storeArr.includes(split[1])){
            storeArr.push(split[1])
        }
        storeObjArr.push({
            city: split[0],
            store: split[1],
        })
    })
    
    // Create option for store select tag
    const createStoreOptions = () =>{
        // <input type="radio" id="html" name="fav_language" value="HTML">
        // <label for="html">HTML</label><br>
        if(!storeArr.length) return;
        
        storeArr.forEach((item, index)=>{
            let p = document.createElement("p")
            let option = document.createElement("input")
            let label = document.createElement("label")
            option.value = item
            option.type = 'radio'
            option.id = item
            option.name = "billing_stores"
            option.for = item
            // Select default
            // if(index === 0){
            //     option.required = true
            // }
            
            // Update label tag
            label.textContent = item
            label.htmlFor = item
            
            // Append tag
            p.appendChild(option)
            p.appendChild(label)
            storeSelect.appendChild(p)
        })
        billingStoresField.appendChild(storeSelect)
    }
    
    createStoreOptions()
    
    // Set city to none 
    billingCity.innerHTML = `<option value="0">Select Store</option>`;
    
    const updateAreaOptions = (value)=>{
        billingCity.innerHTML = `<option value="0">Select Store</option>`;
        storeObjArr.forEach((item, index)=>{
            if(value === item.store){
                let option = document.createElement("option")
                option.value = `${item.city}--${item.store}`
                option.textContent = item.city
                billingCity.appendChild(option)
            }
        })
        
    }
    
    // Add event lister to store select.
    // when a store is clicked, populate matched areas
    storeSelect.addEventListener('change', (e)=>{
        const value = e.target.value || e.target.textContent
        if(storeArr.includes(value)){
            updateAreaOptions(value)
        }
        
    })

});