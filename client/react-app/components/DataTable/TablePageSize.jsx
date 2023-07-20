import React from "react";

import Select from "../UI/Select";

const TablePageSize = ({ pageSize, setPageSize, allDataSize }) => {
  return (
    <Select
        options={[{ value: '5', label: '5' },{ value: '10', label: '10' },{ value: '20', label: '20' },{ value: '50', label: '50' },{ value: '100', label: '100' }, { value: allDataSize.toString(), label: 'Все' }]}
        selected={pageSize.toString()}
        callback={(value) => setPageSize(Number(value))}
    />
  );
}

export default TablePageSize;
