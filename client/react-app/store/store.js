import { configureStore } from '@reduxjs/toolkit';

import { serviceAPI, emojiMart } from '../service/API';
import globalReducer from './reducers/globalSlice';

import { localStorageMiddleware, loadFromLocalStorage } from './middlewares';

export default configureStore({
  reducer: {
    global: globalReducer,
    [serviceAPI.reducerPath]: serviceAPI.reducer,
    [emojiMart.reducerPath]: emojiMart.reducer
  },
  preloadedState: loadFromLocalStorage(),
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      serviceAPI.middleware,
      emojiMart.middleware,
      localStorageMiddleware.middleware
    )
});
