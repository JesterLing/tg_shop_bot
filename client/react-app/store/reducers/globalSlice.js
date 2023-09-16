import { createSlice } from '@reduxjs/toolkit';

const initialState = {
  isAuth: false,
  user: null,
  themeDark: false,
  menuFull: false,
  configs: { type: null, currency: null },
  refreshToken: null,
  uploadProgress: 0
};

const globalSlice = createSlice({
  name: 'global',
  initialState,
  reducers: {
    setUploadProgress: (state, { payload }) => {
      state.uploadProgress = payload;
    },
    auth: (state, { payload }) => {
      state.isAuth = true;
      state.refreshToken = payload.refresh_token;
      state.user = payload.user;
    },
    deauth: (state) => {
      state.isAuth = false;
      state.refreshToken = null;
      state.user = null;
    },
    setConfigs: (state, { payload }) => {
      for (const key in payload) {
        state.configs[key] = payload[key];
      }
    },
    toggleTheme: (state) => {
      state.themeDark = !state.themeDark;
    },
    toggleMenu: (state) => {
      state.menuFull = !state.menuFull;
    }
  }
});

export const { setUploadProgress, auth, deauth, setConfigs, toggleTheme, toggleMenu } =
  globalSlice.actions;
export default globalSlice.reducer;
