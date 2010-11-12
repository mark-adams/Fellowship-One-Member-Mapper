/*
* Member Mapper Client
* @author Mark Adams (C) 2010
*
*/

(function($,google){
	
	var interviewApp = function(){
		
		var map;					// The Google Map object
		var status;					// The status box element
		var count;					// The number of addresses loaded
		var openInfoWindows = [];	// Array of open info windows (for use when closing all info windows)
		var markers = [];			// Array of currently placed markers used for avoiding duplicates
		
		/* Update the status display on the page to the specified value */
		function changeStatus(newStatus,loading){
			// Set the new status value
			status.innerHTML = newStatus;
			
			if (loading){
				// If the loading graphic is to be displayed
				// Create the loading image element and append it to the status text
				
				var img = document.createElement("img");
				img.src = "images/ajax-loader.gif";
				status.appendChild(img);
			}	
		}
		
		/* Closes all currently open Info Windows */
		function closeAllInfoWindows(){
			while (openInfoWindows.length){
				// As long as elements exist in the array, pop them and close them.
				var iw = openInfoWindows.pop();
				iw.close();
			}
		}
		
		/* Makes a name entry editable */
		function makeEditable(clicked,container,person,marker){

			// Save the existing text of the clicked link
			var text = clicked.innerHTML;
			
			// Clear its container
			container.innerHTML = '';
			
			// Create a new div to build in
			var editContainer = document.createElement("div");
		
			// Create an text input field and give it the same value as the old text
			var field = document.createElement("input");
			field.type = "text";
			field.value = text;
			
			// Create a save link.
			var save = document.createElement("a");
			save.href = "#";
			save.innerHTML = "[Save]";
			
			// Create a cancel link
			var cancel = document.createElement("a");
			cancel.href = "#";
			cancel.innerHTML = "[Cancel]";
			
			// Add a click handler to update the person's name.
			$(save).click(function(){
				updatePersonName(person,marker,container,field.value);
			})
			
			// Add a cancel handler to return the container to its original state.
			$(cancel).click(function(){
				configureNameField(person,container);
			});
			
			// Place all the elements in the edit container.
			editContainer.appendChild(field);
			editContainer.appendChild(save);
			editContainer.appendChild(cancel);
			editContainer.className = "edit";
			
			// Insert the edit div into the container.
			container.appendChild(editContainer);
			
			// Give the field focus
			field.focus();
		}
		
		/* Displays the info box for a particular marker. */
		function showInfoBox(sender){
			// Close all currently open info boxes
			closeAllInfoWindows();
			
			// Create a div to hold the info box content
			var content = document.createElement('div');
			content.className = "infoBox";
			
			// Put the address as a header
			$(content).append("<h2>" + sender.title + "</h2>");
			
			// Create an unordered list to hold the people
			var peopleList = document.createElement('ul');
			
			// For each person in the list create an list item
			$(sender.people).each(function(pIdx){
				var person = sender.people[pIdx];
				var item = document.createElement("li");
				
				// Create a clickable link for editing
				configureNameField(person,item,sender);
				
				// Add the list item to the list
				peopleList.appendChild(item);
			});
			
			// Add the completed list to the content div
			content.appendChild(peopleList);
			
			// Set the info box to use the content element and the marker's position
			var infoOpts = {
				content: content,
				position: sender.position
			}
			
			// Create the info window
			var infoWindow = new google.maps.InfoWindow(infoOpts);
			
			// Add an event listener to ensure that all info windows are closed when the close button is clicked
			google.maps.event.addListener(infoWindow, 'closeclick', function() {
			    openInfoWindows.splice($.inArray(this,openInfoWindows));
			  });
			
			// SHow the info window
			infoWindow.open(map);
			
			// Add it to the list of open info windows
			openInfoWindows.push(infoWindow);
		}
		
		/* Create a clickable, editable field for the specific person */
		function configureNameField(person,container, marker){
			// Clear the container element
			container.innerHTML = "";
			
			// Create a link using the person's first and last name
			var link = document.createElement("a");
			link.href = "#";
			link.innerHTML = person.firstName + ' ' + person.lastName;
			
			// When it is clicked, make the editable field
			$(link).click(function(){
				makeEditable(this,container,person,marker);
			});
			
			// Put the link in the container
			container.appendChild(link);
		}
		
		/* Perform the AJAX request to retrieve a page of addresses from Fellowship One */
		function getAddresses(page){
			// Prepare the options for the request
			var options = {
				o: "getAddresses",
				page: page
			}
			
			// Fire the AJAX request
			$.ajax({
				url: "services/",
				type: "GET",
				dataType: "json",
				data: options,
				success: processAddressesCallback,
				error: genericAjaxErrorCallback
			});
		
			
			// Change the status
			changeStatus("Loading people (" + count + " loaded so far)...", true);
		}
	
		/* Callback for getAddresses AJAX request */
		function processAddressesCallback(responseData){
			if (responseData.status == "OK"){
				// If the request is succesful...
				if (responseData.apiCount > 0){
					// and the API was still returning results
					// (Sidenote: API count tells us how many results the API request actually returned before
					// Nongeocodable addresses were removed. If this is > 0, it means more pages might exist.)
					addresses = responseData.data.addresses
					for (var address in addresses){
						// Go through each address...
						var addressObj = addresses[address];
						var marker = null;
						
						// and check to see if a marker has already been placed for it.
						if (typeof(markers[address]) == "undefined"){
							// If not, create one
							marker = new google.maps.Marker({
							      position: new google.maps.LatLng(addressObj.location.lat,addressObj.location.lng), 
							      map: map, 
							      title: address,
								  clickable: true
							  });
							
							// Attach the people to the marker
							marker.people = addressObj.people;
							
							// Add an onClick handler to show the inf obox
							google.maps.event.addListener(marker, 'click', function() {
							    showInfoBox(this);
							  });
							
							// Save the marker
							markers[address] = marker;

						}else{
							// If a marker already exists, add the people to the people list on that marker.
							marker = markers[address];
							marker.people = [].concat(marker.people,addressObj.people);
						}

					}
					
					// Update the count with the latest entries.
					count += responseData.dataCount;
					
					// Get the next page
					getAddresses(responseData.page + 1);

				}else{
					// If no more pages exist, change status to say loading is complete.
					changeStatus("Loading Complete.");
				}
			}else{
				// Show the error message if an error occured.
				alert(responseData.data);
			}
		}
		
		/* Perform the AJAX request to update a person's name in Fellowship One */
		function updatePersonName(person,marker,container,name){
			// Add the loading image to show something is happening.
			var img = document.createElement("img");
			img.src = "images/ajax-loader.gif";
			container.children[0].appendChild(img);
			
			// Prepare the parameters for the request
			var options = {
				o: "updatePersonName",
				id: person["@id"],
				name: name
			}
	
			// Fire the AJAX request
			$.ajax({
				url: "services/",
				type: "POST",
				dataType: "json",
				data: options,
				success: function(data){
					processUpdateNameCallback(data,person,marker,container);
				},
				error: genericAjaxErrorCallback
			});
		}
		
		/* Callback for updatePersonName AJAX request */
		function processUpdateNameCallback(data,orgPerson,marker,container){
			if (data.status == "OK"){
				// If the request is succesful, set the field back to normal
				configureNameField(data.data.person,container);
				pIdx = $.inArray(orgPerson,marker.people);
				marker.people[pIdx] = data.data.person;
			}else{
				// If not, show the error
				alert(data.data);
				configureNameField(orgPerson,container);
			}
		}
		
		function genericAjaxErrorCallback(request, textStatus, errorThrown){
			alert("An error occured while processing the request (" + textStatus + ":" +  errorThrown + "). Please refresh and try again.");
		}
		
		/* Initialize the application */
		function init(){
			// Set the height of the map canvas to fill the browser
			var headerHeight = document.getElementById("header").clientHeight + 1;
			var windowHeight = $(window).height();
			var mapCanvas = document.getElementById("map_canvas");
			mapCanvas.style.height = (windowHeight - headerHeight) + "px";
			
			// Initialize the status box
			status = document.getElementById("statusBox");
		
			count = 0;
			
			// Initalize the options for our map
			var center = new google.maps.LatLng(32.814, -96.913);
			var mapOptions = {
				zoom: 10,
				center: center,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			
			// Create the map
			map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
			
			// Begin getting addresses
			getAddresses(1);
			
			// Update the status box
			changeStatus("Loading member addresses...",true);
		}
		
		/* Expose init globally in case it needs to be recalled. */
		return{
			init: init
		}
	}();
	
	// Fire init on document ready
	$(document).ready(interviewApp.init);
	
	// Expose interviewApp public methods in the global namespace
	window.interviewApp = interviewApp;
})(jQuery,google);