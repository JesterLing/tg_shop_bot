import React from 'react';

import Radio from './Radio';

const Radios = ({ options, selected, ...props }) => (
  <div className="form-group">
    {options.map((option, i) => (
      <Radio
        key={i}
        id={option.value}
        value={option.value}
        label={option.label}
        caption={option.caption}
        checked={option.value == selected ? true : false}
        disabled={option.disabled}
        {...props}
      />
    ))}
  </div>
);

export default Radios;
