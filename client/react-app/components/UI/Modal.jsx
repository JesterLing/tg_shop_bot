import React from "react";

import styles from "./Modal.module.less";

const Modal = ({ children, visible, buttons = null, header = null, headerCaption = null }) => {
    return(
        <div className={`fullpage-modal-container ${visible.get ? styles.visible : styles.hide}`}>
        <div className="overlay"></div>
        <div className="fullpage-modal-inner-wrap">
            <div className="fullpage-modal">
            <span className="close-btn" onClick={() => visible.set(false)}></span>
            {(header || headerCaption) &&
                <div className="modal-header">
                    { header && <h3 className="modal-header-text">{header}</h3> }
                    { headerCaption && <p className="sub">{headerCaption}</p> }
                </div>
            }
            <div className={`modal-body ${styles.body}`}>
                {children}
            </div>
            {buttons && 
                <div className="modal-footer btn-row">{buttons()}</div>
            }
            </div>
        </div>
        </div>
    );
}

export default Modal;