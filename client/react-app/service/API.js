import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

import { setUploadProgress } from '../store/reducers/globalSlice';
import { reAuthQuery, baseQueryWithReauth } from './customFetch';

export const baseQuery = fetchBaseQuery({
  baseUrl: process.env.PUBLIC_URL,
  prepareHeaders: (headers) => {
    headers.set('X-Requested-With', 'Backend ' + process.env.VERSION);
    return headers;
  }
});

export const serviceAPI = createApi({
  reducerPath: 'serviceAPI',
  tagTypes: ['Categories', 'Goods', 'Settings', 'Admins'],
  baseQuery: baseQueryWithReauth,
  endpoints: (build) => ({
    dashboardInfo: build.query({
      query: () => `/dashboard`
    }),
    loginBySecret: build.mutation({
      query: (secret) => ({
        url: '/auth',
        method: 'POST',
        body: { secret: secret }
      })
    }),
    firstStart: build.query({
      query: (step) => `/firststart/${step}`
    }),
    setFirstStart: build.mutation({
      query: (data) => ({
        url: `/firststart`,
        method: 'POST',
        body: data
      })
    }),
    upload: build.mutation({
      queryFn: async (data, api) => {
        return new Promise(function (resolve, reject) {
          var xhr = new XMLHttpRequest();
          xhr.open('POST', '/files/upload', true);
          xhr.responseType = 'json';
          xhr.upload.onprogress = (event) => {
            const progress = Math.round((100 * event.loaded) / event.total);
            api.dispatch(setUploadProgress(progress));
          };
          xhr.onload = async () => {
            if (xhr.status >= 200 && xhr.status < 300) {
              resolve({ data: xhr.response });
            } else {
              if (xhr.status == 401 && xhr.response?.message === 'Предоставленный токен истек') {
                const refreshResult = await reAuthQuery(api);
                if (refreshResult.data?.type == 'success') {
                  xhr.open('POST', '/files/upload', true);
                  xhr.send(data);
                } else {
                  reject({
                    error: { status: xhr.status, statusText: xhr.statusText, data: xhr.response }
                  });
                }
              } else {
                reject({
                  error: { status: xhr.status, statusText: xhr.statusText, data: xhr.response }
                });
              }
            }
          };
          xhr.onerror = () => {
            reject({
              error: { status: xhr.status, statusText: xhr.statusText, data: xhr.response }
            });
          };
          xhr.send(data);
        });
      }
    }),
    getCategories: build.query({
      query: () => `/categories`,
      providesTags: (result, error, arg) =>
        result
          ? [...result.map(({ id }) => ({ type: 'Categories', id })), 'Categories']
          : ['Categories']
    }),
    addCategory: build.mutation({
      query: (data) => ({
        url: '/categories',
        method: 'POST',
        body: data
      }),
      invalidatesTags: ['Categories']
    }),
    editCategory: build.mutation({
      query: (data) => ({
        url: '/categories/edit',
        method: 'PUT',
        body: data
      }),
      invalidatesTags: ['Categories']
    }),
    orderCategory: build.mutation({
      query: (data) => ({
        url: '/categories/order',
        method: 'PUT',
        body: data
      }),
      invalidatesTags: ['Categories']
    }),
    deleteCategory: build.mutation({
      query: (data) => ({
        url: '/categories',
        method: 'DELETE',
        body: data
      }),
      invalidatesTags: ['Categories']
    }),
    getGoods: build.query({
      query: () => `/goods`,
      providesTags: (result, error, arg) =>
        result ? [...result.map(({ id }) => ({ type: 'Goods', id })), 'Goods'] : ['Goods']
    }),
    getProduct: build.query({
      query: (id) => `/goods/${id}`
    }),
    addProduct: build.mutation({
      query: (data) => ({
        url: '/goods',
        method: 'POST',
        body: data
      }),
      invalidatesTags: ['Goods']
    }),
    editProduct: build.mutation({
      query: (data) => ({
        url: '/goods',
        method: 'PUT',
        body: data
      })
    }),
    delProduct: build.mutation({
      query: (ids) => ({
        url: '/goods',
        method: 'DELETE',
        body: ids
      }),
      invalidatesTags: ['Goods']
    }),
    getUsers: build.query({
      query: (id) => `/users`
    }),
    mailing: build.mutation({
      query: (data) => ({
        url: '/users/mailing',
        method: 'POST',
        body: data
      })
    }),
    getAdmins: build.query({
      query: () => `/settings/admins`,
      providesTags: (result, error, arg) =>
        result ? [...result.map(({ id }) => ({ type: 'Admins', id })), 'Admins'] : ['Admins']
    }),
    setAdmin: build.mutation({
      query: (data) => ({
        url: '/settings/admins',
        method: 'POST',
        body: data
      }),
      invalidatesTags: ['Admins']
    }),
    delAdmin: build.mutation({
      query: (data) => ({
        url: '/settings/admins',
        method: 'DELETE',
        body: data
      }),
      invalidatesTags: ['Admins']
    }),
    getPurchases: build.query({
      query: () => `/purchases`
    }),
    getPurch: build.query({
      query: (id) => `/purchases/${id}`
    }),
    getPayment: build.query({
      query: (id) => `/purchases/payment/${id}`
    }),
    getSettings: build.query({
      query: () => `/settings`,
      providesTags: () => ['Settings']
    }),
    setSettings: build.mutation({
      query: (data) => ({
        url: '/settings',
        method: 'POST',
        body: data
      }),
      invalidatesTags: ['Settings']
    })
  })
});

export const {
  useDashboardInfoQuery,
  useLoginBySecretMutation,
  useFirstStartQuery,
  useSetFirstStartMutation,
  useGetCategoriesQuery,
  useLazyGetCategoriesQuery,
  useAddCategoryMutation,
  useEditCategoryMutation,
  useOrderCategoryMutation,
  useDeleteCategoryMutation,
  useGetGoodsQuery,
  useLazyGetProductQuery,
  useAddProductMutation,
  useEditProductMutation,
  useDelProductMutation,
  useUploadMutation,
  useGetUsersQuery,
  useMailingMutation,
  useGetPurchasesQuery,
  useGetPurchQuery,
  useGetPaymentQuery,
  useGetSettingsQuery,
  useSetSettingsMutation,
  useGetAdminsQuery,
  useSetAdminMutation,
  useDelAdminMutation
} = serviceAPI;

export const emojiMart = createApi({
  reducerPath: 'emojiMart',
  keepUnusedDataFor: 86400,
  baseQuery: fetchBaseQuery({ baseUrl: 'https://cdn.jsdelivr.net/npm/@emoji-mart/' }),
  endpoints: (build) => ({
    emojiSet: build.query({
      query: () => `data`
    }),
    emojiI18n: build.query({
      query: () => `data@latest/i18n/ru.json`
    })
  })
});

export const { useEmojiSetQuery, useEmojiI18nQuery } = emojiMart;
