import React from 'react';

import Header from '../Header/Header';
import Footer from '../Footer/Footer';
import { NavigationLeft } from '../Navigation/Navigation';

export default ({ children }) => {
  return (
    <div>
      <Header />
      <div className="warp">
        <div className="nav-pane">
          <NavigationLeft />
        </div>
        <div className="content-pane">{children}</div>
      </div>
      <Footer />
    </div>
  );
};
