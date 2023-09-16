import { Mutex } from 'async-mutex';

import { deauth, setConfigs } from '../store/reducers/globalSlice';
import { baseQuery } from './API';

const mutex = new Mutex();

export const baseQueryWithReauth = async (args, api, extraOptions) => {
  await mutex.waitForUnlock();
  let result = await baseQuery(args, api, extraOptions);
  if (result.error) {
    if (
      result.error.status == 401 &&
      result.error.data?.message === 'Предоставленный токен истек'
    ) {
      if (api.getState().global.isAuth) {
        if (!mutex.isLocked()) {
          const release = await mutex.acquire();
          try {
            const refreshResult = await reAuthQuery(api, extraOptions);
            if (refreshResult.data?.type == 'success') {
              result = await baseQuery(args, api, extraOptions);
            } else {
              api.dispatch(deauth());
              window.location.href = '/';
              return refreshResult;
            }
          } finally {
            release();
          }
        } else {
          await mutex.waitForUnlock();
          result = await baseQuery(args, api, extraOptions);
        }
      }
    }
  }
  return result;
};

export const reAuthQuery = async (api, extraOptions) => {
  return await baseQuery(
    { url: '/refresh', method: 'POST', body: { refresh: api.getState().global.refreshToken } },
    api,
    extraOptions
  );
};
