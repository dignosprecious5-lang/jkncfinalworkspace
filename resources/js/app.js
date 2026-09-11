function openDealDrawer(){

document.getElementById('dealDrawer')
.classList.add('active');


document.getElementById('dealOverlay')
.style.display='block';


}



function closeDealDrawer(){

document.getElementById('dealDrawer')
.classList.remove('active');


document.getElementById('dealOverlay')
.style.display='none';


}
