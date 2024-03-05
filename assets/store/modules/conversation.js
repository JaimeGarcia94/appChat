export default {
    state: {
        conversations: [],
        hubUrl: null
    },
    getters: {
        CONVERSATIONS: state => {
            return state.conversations.sort((a, b) => {
                return a.createdAt < b.createdAt;
            })
        },
        MESSAGES: state => conversationId => {
            var objectConversation = state.conversations.find(i => i.conversationId == conversationId).messages;

            return objectConversation;
        },
        HUBURL: state => state.hubUrl
    },
    mutations: {
        SET_CONVERSATIONS: (state, payload) => {
            state.conversations = payload
        },
        SET_MESSAGES: (state, {conversationId, payload}) => {
            var objectConversation = state.conversations.find(i => i.conversationId == conversationId);
            var addPropertyConversation = objectConversation['messages'] = payload;

            return addPropertyConversation;
        },
        ADD_MESSAGE: (state, {conversationId, payload}) => {
            var objectConversation = state.conversations.find(i => i.conversationId == conversationId);
            var addPropertyConversation = objectConversation['messages'].push(payload);

            return addPropertyConversation;
        },
        SET_CONVERSATION_LAST_MESSAGE: (state, {conversationId, payload}) => {
            let rs = state.conversations.find(i => i.conversationId == conversationId);
            rs.content = payload.content;
            rs.createdAt = payload.createdAt;
        },
        SET_HUBURL: (state, payload) => state.hubUrl = payload,
        UPDATE_CONVERSATIONS: (state, payload) => {
            let rs = state.conversations.find(i => i.conversationId == payload.conversation.id);
            rs.content = payload.content;
            rs.createdAt = payload.createdAt;
        }
    },
    actions: {
        GET_CONVERSATIONS: ({commit}) => {
            return fetch(`/conversations`)
                .then(result => {
                    const hubUrl = result.headers.get('Link').match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/)[1]
                    commit("SET_HUBURL", hubUrl)
                    return result.json()
                })
                .then((result) => {
                    commit("SET_CONVERSATIONS", result)
                })
        },
        GET_MESSAGES: ({commit, getters}, conversationId) => {
            if (getters.MESSAGES(conversationId) === undefined) {
                return fetch(`/messages/${conversationId}`)
                    .then(result => result.json())
                    .then((result) => {
                        commit("SET_MESSAGES", {conversationId, payload: result})
                    });
            }

        },
        POST_MESSAGE: ({commit}, {conversationId, content}) => {
            let formData = new FormData();
            formData.append('content', content);

            return fetch(`/messages/${conversationId}`, {
                method: "POST",
                body: formData
            })
                .then(result => result.json())
                .then((result) => {
                    commit("ADD_MESSAGE", {conversationId, payload: result})
                    commit("SET_CONVERSATION_LAST_MESSAGE", {conversationId, payload: result})
                })
        }
    }
}