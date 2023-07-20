import React, { useState } from "react";

const Select = ({ options, selected, callback, ...props }) => {
    const [open, setOpen] = useState(false);
    const change = (e) => {
        callback(e.target.dataset.value);
        e.stopPropagation();
        setOpen(false);
    }
    const items = options.map((option, index) =>
        <option key={index} value={option.value} disabled={option.disabled}>{option.label}</option>
    )
    const spans = options.map((option, index) =>
        <span key={index} onClick={change} className={`selecter-item${option.value === selected ? ' selected' : ''}${option.disabled ? ' disabled' : ''}`} data-value={option.value}>{option.label}</span>
    )
    
    return (
        <div tabIndex={0} className={`selecter cover${open ? ' open focus' : ' closed'}`} onClick={() => setOpen(true)} onBlur={() => setOpen(false)}>
            <select name="selectbox" className="selectbox selecter-element" {...props}>
                {items}
            </select>
            <span className="selecter-selected">{ options.find(o => o.value === selected)?.label.trim() }</span>
            <div className="selecter-options" style={{ display: open ? 'block': 'none'}}>
                {spans}
            </div>
        </div>
    );
};

export default Select;
