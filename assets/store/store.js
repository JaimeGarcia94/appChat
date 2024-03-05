import { createApp } from 'vue';
import { createStore } from 'vuex';
import conversation from './modules/conversation';
import user from './modules/user';

const store = createStore({
    modules: {
        conversation,
        user
    }
})

// createApp().use(store);
export default store;

