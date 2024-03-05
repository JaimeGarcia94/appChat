import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from "vue-router";
import App from './components/App.vue'
import store from './store/store.js'
import Blank from './components/Right/Blank.vue';
import Right from './components/Right/Right.vue';

const routes = [
{ 
    name: 'blank', 
    path: "/", 
    component: Blank 
},
{ 
    name: 'conversation', 
    path: "/conversation/:id", 
    component: Right 
},

];

store.commit("SET_USERNAME", document.querySelector('#app').dataset.username);

const history = createWebHashHistory();

const router = createRouter({
    history,
    routes,
});

// createApp(App).use(store).mount('#app')
createApp(App).use(store).use(router).mount('#app')

router.replace('/');


import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
