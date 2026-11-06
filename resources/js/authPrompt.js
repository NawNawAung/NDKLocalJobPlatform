import { reactive } from 'vue';

export const authPromptState = reactive({ open: false, context: 'account' });

export function requestAuthentication(context = 'account') {
    authPromptState.context = context;
    authPromptState.open = true;
}

export function closeAuthenticationPrompt() {
    authPromptState.open = false;
}
