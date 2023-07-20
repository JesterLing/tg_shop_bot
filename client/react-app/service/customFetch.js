import { Mutex } from 'async-mutex';

import { deauth, setConfigs } from '../store/reducers/globalSlice';
import { baseQuery } from './API';

const mutex = new Mutex();

import data from '../../gh-pages/gh-pages-defaults.json';

const find = (array = [], key, value) => {
  for (const item of array) {
    const result = item[key] === value ? item : find(item.children, key, value);
    if (result) return result;
  }
};

export const baseQueryWithReauth = async (args, api, extraOptions) => {
  return new Promise(function (resolve, reject) {
    if (api.type == 'query') {
      switch (api.endpoint) {
        case 'dashboardInfo':
          resolve({ data: data.dashboard });
          break;
        case 'getCategories':
          resolve({ data: data.categories });
          break;
        case 'getGoods':
          resolve({ data: data.goods });
          break;
        case 'getProduct':
          var id = args.match(/\d/g).join('');
          var prod = JSON.parse(JSON.stringify(data.goods.filter((x) => x.id == id)));
          prod[0].name = prod[0].pname;
          prod[0].category = find(data.categories, 'name', prod[0].cname).id;
          resolve({ data: prod[0] });
          break;
        case 'getPurch':
          var id = args.match(/\d/g).join('');
          resolve({ data: data.purch.filter((x) => x.order_id == id) });
          break;
        case 'getPayment':
          var id = args.match(/\d/g).join('');
          var find = data.payment.filter((x) => x.id == id);
          resolve({ data: find ? find[0] : [] });
          break;
        case 'getSettings':
          resolve({ data: data.settings });
          break;
        default:
          if (data.hasOwnProperty(api.endpoint)) {
            resolve({ data: data[api.endpoint] });
          } else {
            reject({
              error: { status: 404, statusText: 'Not Found', data: { message: 'Обьект не найден' } }
            });
          }
          break;
      }
    }
    if (api.type == 'mutation') {
      switch (api.endpoint) {
        case 'addCategory':
          var newdata = JSON.parse(JSON.stringify(data.categories));
          newdata.push(args.body);
          data.categories = newdata;
          break;
        case 'editCategory':
          var newdata = JSON.parse(JSON.stringify(data.categories));
          var edited = newdata.map((x) => {
            if (x.children) {
              x.children = x.children.map((y) => (y.id == args.body.id ? args.body : y));
              return x;
            } else {
              return x.id == args.body.id ? args.body : x;
            }
          });
          data.categories = edited;
          break;
        case 'orderCategory':
          data.categories = args.body;
          break;
        case 'deleteCategory':
          var newdata = JSON.parse(JSON.stringify(data.categories));
          newdata = newdata.filter((item) => item.id !== args.body.id);
          data.categories = newdata;
          break;
        case 'addProduct':
          var newdata = JSON.parse(JSON.stringify(data.goods));
          args.body.pname = args.body.name;
          args.body.cname = find(data.categories, 'id', args.body.category).name;
          args.body.id = Math.floor(Math.random() * 100) + 100;
          newdata.push(args.body);
          data.goods = newdata;
          console.log(data.goods);
          break;
        case 'setSettings':
          var newdata = JSON.parse(JSON.stringify(data.settings));
          data.settings = args.body;
          api.dispatch(setConfigs({ type: data.settings.type }));
          break;
      }
      resolve({ data: { type: 'success' } });
    }
  });
};

// export const baseQueryWithReauth = async (args, api, extraOptions) => {
//   await mutex.waitForUnlock();
//   let result = await baseQuery(args, api, extraOptions);
//   if (result.error) {
//     if (
//       result.error.status == 401 &&
//       result.error.data?.message === 'Предоставленный токен истек'
//     ) {
//       if (api.getState().global.isAuth) {
//         if (!mutex.isLocked()) {
//           const release = await mutex.acquire();
//           try {
//             const refreshResult = await reAuthQuery(api, extraOptions);
//             if (refreshResult.data?.type == 'success') {
//               result = await baseQuery(args, api, extraOptions);
//             } else {
//               api.dispatch(deauth());
//               window.location.href = '/';
//               return refreshResult;
//             }
//           } finally {
//             release();
//           }
//         } else {
//           await mutex.waitForUnlock();
//           result = await baseQuery(args, api, extraOptions);
//         }
//       }
//     }
//   }
//   return result;
// };

export const reAuthQuery = async (api, extraOptions) => {
  return await baseQuery(
    { url: '/refresh', method: 'POST', body: { refresh: api.getState().global.refreshToken } },
    api,
    extraOptions
  );
};
