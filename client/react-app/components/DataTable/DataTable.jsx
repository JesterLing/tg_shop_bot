// @ts-nocheck
import React, { useEffect, Fragment } from "react";
import { useTable, usePagination, useRowSelect, useGlobalFilter, useGroupBy, useExpanded, useSortBy } from "react-table";
import TablePageSize from "./TablePageSize";
import TableSearch from "./TableSearch";

import styles from "./DataTable.module.less";

const DataTable = ({columns, data, renderRowSubComponent = null, selectedRows = null, setSelectedRows = null, getRowId, group }) => {
  const {
    state,
    toggleAllRowsExpanded,
    getTableProps,
    getTableBodyProps,
    headerGroups,
    page,
    prepareRow,
    visibleColumns,
    selectedFlatRows,
    preGlobalFilteredRows,
    setGlobalFilter,
    toggleGroupBy,
    canPreviousPage,
    canNextPage,
    pageOptions,
    pageCount,
    gotoPage,
    nextPage,
    previousPage,
    setPageSize 
  } = 
  useTable({ columns, data, getRowId }, useGlobalFilter, useGroupBy, useSortBy, useExpanded, usePagination, useRowSelect );

  useEffect(() => {
    if(setSelectedRows && selectedRows) setSelectedRows(selectedFlatRows);
  }, [state.selectedRowIds]);
  
  return (
      <div>
        <div className={styles.topControls}>
          <div><TableSearch preGlobalFilteredRows={preGlobalFilteredRows} globalFilter={state.globalFilter} setGlobalFilter={setGlobalFilter}/></div>
          <div>{ group(toggleGroupBy, toggleAllRowsExpanded) }</div>
          <div><TablePageSize pageSize={state.pageSize} setPageSize={setPageSize} allDataSize={data.length}/></div>
        </div>
        <table className={`table table-outer-borders table-inner-borders table-striped${(setSelectedRows && selectedRows) ? ' table-hover' : ''} ${styles.dataTable}`}>
          <thead>
            {headerGroups.map(headerGroup => (
              <tr {...headerGroup.getHeaderGroupProps()}>
                {headerGroup.headers.map(column => (
                  <th className={`${column.canSort ? column.isSorted ? (column.isSortedDesc ? styles.sorting_desc : styles.sorting_asc) : styles.sorting : ''} ${column.className ? column.className: ''}`} {...column.getHeaderProps(column.getSortByToggleProps())}>
                    {column.render('Header')}
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody {...getTableBodyProps()}>
            {page.map((row, i) => {
              prepareRow(row);
              const rowProps = row.getRowProps({ onClick: () => { if(setSelectedRows && selectedRows) row.toggleRowSelected(!row.isSelected);}, className: row.isSelected ? styles.selected : '' });
              return (
                <React.Fragment key={rowProps.key}>
                <tr {...rowProps}>
                  {row.cells.map(cell => {
                    return <td {...cell.getCellProps()}>
                    {
                      cell.isGrouped ? (
                        // If it's a grouped cell, add an expander and row count { }
                        <>
                          <span {...row.getToggleRowExpandedProps({ onClick : (e) => { e.stopPropagation(); row.toggleRowExpanded(); }, className: row.isExpanded ? (styles.expand + ' ' + styles.close) : (styles.expand + ' ' + styles.open)})}></span>
                          {cell.render('Cell')} ({row.subRows.length})
                        </>
                      ) : cell.isAggregated ? (
                        // If the cell is aggregated, use the Aggregated
                        // renderer for cell
                        cell.render('Aggregated')
                      ) : cell.isPlaceholder ? null : ( // For cells with repeated values, render null
                        // Otherwise, just render the regular cell
                        cell.render('Cell')
                      )
                    }</td>
                  })}
                </tr>
                {(row.isExpanded && !row.isGrouped) &&
                renderRowSubComponent({ row, rowProps, visibleColumns })}
              </React.Fragment>)
            })}
          </tbody>
        </table>
        <div className={styles.bottomControls}>
          <div>Выбрано: {Object.keys(state.selectedRowIds).length}</div>
          <div>
          <nav className="paging">
            <div className={`prev pg-block${!canPreviousPage ? ' disabled' : ''}`} onClick={() => previousPage()}><a><i className="petalicon"></i>Пред</a></div>
            <ul className="pg-list">
              { pageCount < 6 ?
                  <Fragment>
                    { (() => {
                      const paging = [];
                      for (let index = 0; index < pageCount; index++) {
                        paging.push(<li className={`pg-block${state.pageIndex == index ? ' active' : ''}`} key={index} onClick={() => gotoPage(index)}><a>{index + 1}</a></li>);
                      }
                      return paging;
                    })()}
                  </Fragment>
                :
                <li className='pg-block active'><a>{state.pageIndex + 1}</a></li>
              }

            </ul>
            <div className={`next pg-block${!canNextPage ? ' disabled' : ''}`} onClick={() => nextPage()}><a>След<i className="petalicon"></i></a></div>
          </nav>

          </div>
          <div>Показано {page.length} { data.length > page.length && <Fragment> из {data.length}</Fragment> }
          </div>
        </div>
      </div>
      );
}

export default DataTable;