import React, { useState } from "react";

import styles from "./TabWrapper.module.less";

const TabsWrapper = ({ children, style = null }) => {
  const [activeTab, setActiveTab] = useState(0);

  return (
    <div className={`tab-group ${styles.wrapper} ${style ? ' ' + style : ''}`} >
        {children.map((child, i) => {
            return(
                <div key={i} className={`tab${activeTab === i ? ' selected' : ''}${child.props.disabled ? ' disabled' : ''}`} tabIndex={0} onClick={() => setActiveTab(i) }>
                    {child.props.icon && <span className="tab-icon"><i className={`petalicon petalicon-${child.props.icon}`}></i></span>}
                    <span className="tab-label">{child.props.label}</span>
                    {child.props.badge && <span className="badge">{child.props.badge}</span>}
                </div>
            );
        })}

        <div className="tab spacer"></div>

        {children.map((child, i) => {
            if(activeTab === i) return <div key={i} className={`mv-10 mh-20 ${styles.content}`}>{child}</div>;
            return null;
        })}

    </div>
  );
}

export default TabsWrapper;
