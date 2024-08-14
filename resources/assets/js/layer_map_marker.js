
require('./bootstrap');

// Laravel Mix 6対応。参考：https://zakkuri.life/laravel-mix-npm%E3%81%AE%E3%83%91%E3%83%83%E3%82%B1%E3%83%BC%E3%82%B8%E3%82%92upgrade%E3%81%97%E3%81%9F%E3%82%89%E3%82%B3%E3%83%B3%E3%83%91%E3%82%A4%E3%83%AB%E3%81%A7%E3%81%8D%E3%81%AA%E3%81%8F/
// window.Vue = require('vue').default;
import Vue from 'vue';

Vue.component('layer-map-marker-component', require('./components/LayerMapMarkerComponent').default);

const app = new Vue({
    el: '#app',
});
