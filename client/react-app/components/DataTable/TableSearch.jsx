import React from "react";
import { useAsyncDebounce } from "react-table";
import Input from "../UI/Input";

const TableSearch = ({preGlobalFilteredRows, globalFilter, setGlobalFilter}) => {
    const count = preGlobalFilteredRows.length;
    const [search, setSearch] = React.useState(globalFilter);
    const onChange = useAsyncDebounce(value => {
      setGlobalFilter(value || undefined)
    }, 200);
  
    return (
      <Input
        id="goods_search"
        value={search || ""}
        onChange={e => {
          setSearch(e.target.value);
          onChange(e.target.value);
        }}
        placeholder="Поиск"
      />
    );
}

export default TableSearch;
