import { createListenerMiddleware, isAnyOf } from "@reduxjs/toolkit";

import { auth, deauth, setConfigs, toggleTheme, toggleMenu } from "./reducers/globalSlice";

export const localStorageMiddleware = createListenerMiddleware();

localStorageMiddleware.startListening({
  matcher: isAnyOf(auth, deauth, setConfigs, toggleTheme, toggleMenu),
  effect: (action, listenerApi) => 
    localStorage.setItem('state', JSON.stringify((listenerApi.getState()).global))
});


export const loadFromLocalStorage = () => {
    const state = JSON.parse(localStorage.getItem('state'));
    return {'global': state ? state : undefined};
}