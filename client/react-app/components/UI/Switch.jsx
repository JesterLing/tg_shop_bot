import React from 'react';
import { ConditionalWrapper } from '../Utils';

const Switch = ({ id, labelLeft = null, labelTop = null, labelRight = null, ...props }) => {
    return (
        <ConditionalWrapper condition={labelTop} wrapper={children=> <div className="form-group"><label className="form-label">{labelTop}</label>{children}</div>}>
        <React.Fragment>
            <div className="chk-switch-wrap">
            {labelLeft && <span className="chk-switch-label">{labelLeft}</span>}
            <div className="chk-switch onoff-type">
                <input type="checkbox" id={id} {...props}/>
                <label htmlFor={id}><span></span></label>
            </div>
            {labelRight && <span className="chk-switch-label">{labelRight}</span>}
            </div>
        </React.Fragment>
      </ConditionalWrapper>
    );
}

export default Switch;