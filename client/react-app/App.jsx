import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
import store from './store/store';

import './Global.less';

import { PublicLayout, ProtectedLayout } from './components/Utils';
import LoginPage from './pages/Login/Login';
import DashboardPage from './pages/Dashboard/Dashboard';
import CategoriesPage from './pages/Categories/Categories';
import GoodsPage from './pages/Goods/Goods';
import GoodsForm from './pages/Goods/Form';
import PurchasesPage from './pages/Purchases/Purchases';
import SettingsPage from './pages/Settings/Settings';
import NotFound from './pages/404/NotFound';
import Welcome from './pages/Welcome/Welcome';

class App extends React.Component {
  render() {
    return (
      <BrowserRouter>
        <Routes>
          <Route
            path="/"
            element={
              <PublicLayout>
                <LoginPage />
              </PublicLayout>
            }
          />
          <Route
            path="/welcome"
            element={
              <PublicLayout>
                <Welcome />
              </PublicLayout>
            }
          />
          <Route
            path="/auth/:secret"
            element={
              <PublicLayout>
                <LoginPage />
              </PublicLayout>
            }
          />
          <Route path="*" element={<NotFound />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedLayout>
                <DashboardPage />
              </ProtectedLayout>
            }
          />
          <Route
            path="/categories"
            element={
              <ProtectedLayout>
                <CategoriesPage />
              </ProtectedLayout>
            }
          />
          <Route
            path="/goods"
            element={
              <ProtectedLayout>
                <GoodsPage />
              </ProtectedLayout>
            }
          />
          <Route
            path="/goods/new"
            element={
              <ProtectedLayout>
                <GoodsForm />
              </ProtectedLayout>
            }
          />
          <Route
            path="/goods/:id"
            element={
              <ProtectedLayout>
                <GoodsForm />
              </ProtectedLayout>
            }
          />
          <Route
            path="/purchases"
            element={
              <ProtectedLayout>
                <PurchasesPage />
              </ProtectedLayout>
            }
          />
          <Route
            path="/settings"
            element={
              <ProtectedLayout>
                <SettingsPage />
              </ProtectedLayout>
            }
          />
        </Routes>
      </BrowserRouter>
    );
  }
}

const root = createRoot(document.getElementById('app'));
root.render(
  <Provider store={store}>
    <App />
  </Provider>
);
