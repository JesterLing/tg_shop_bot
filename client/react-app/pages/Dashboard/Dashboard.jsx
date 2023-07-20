import React from 'react';

import Layout from '../../components/Layout/Layout';
import useTitle from '../../hooks/useTitle';
import Alert from '../../components/UI/Alert';
import Panels from './Panels';
import InfoList from './InfoList';
import Mailing from './Mailing';
import { useDashboardInfoQuery } from '../../service/API';

const DashboardPage = () => {
  useTitle('Главная');

  const { data, isLoading, isError, error } = useDashboardInfoQuery();

  return (
    <Layout>
      {isError ? (
        <Alert type="danger" icon={true}>
          {error.data.message}
        </Alert>
      ) : isLoading ? (
        <span className="spinner spinner-demo" id="load-main">
          <span></span>
        </span>
      ) : (
        <section>
          <InfoList {...data.info} />
          <Panels {...data.stats} />
          <Mailing />
        </section>
      )}
    </Layout>
  );
};

export default DashboardPage;
