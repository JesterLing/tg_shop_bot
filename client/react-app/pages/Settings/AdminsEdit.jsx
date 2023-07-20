import React, { useState } from "react";

import Button from "../../components/UI/Button";
import Select from "../../components/UI/Select";
import { collectUserName, formatTimesmap } from "../../components/Utils";
import { useGetUsersQuery, useGetAdminsQuery, useSetAdminMutation, useDelAdminMutation } from '../../service/API';
import Alert from "../../components/UI/Alert";

const AdminsEdit = () => {
    const { data: users, isLoading: isUsersLoading, isError: isUsersError, error: usersError } = useGetUsersQuery();
    const { data: admins, isLoading: isAdminsLoading, isError: isAdminsError, error: adminsError } = useGetAdminsQuery();
    const [add] = useSetAdminMutation();
    const [del] = useDelAdminMutation();
    const [isAdding, setIsAdding] = useState(false);

    if (isAdminsLoading || isUsersLoading) return null;
    if (isUsersError) return <Alert type="danger" icon={true}>{usersError.data.message}</Alert>;
    if (isAdminsError) return <Alert type="danger" icon={true}>{adminsError.data.message}</Alert>;

    const handleAdding = () => {
        let arr = users.filter(item => !admins.some(a => item.id === a.user_id));
        arr = arr.map(item => ({ 'value': item.id, 'label': collectUserName(item) }));
        arr.unshift({ 'value': 0, 'label': 'Выбрать пользователя' });
        return <Select options={arr} selected={0} callback={(value) => { add({ 'user_id': Number(value) }); setIsAdding(false); }} />;
    }

    return (
        <div className="form-group">
            <span className="form-label" style={{ marginTop: "20px" }}>Администраторы</span>
            <ul style={{listStyle: "none"}}>
                {admins.map((admin) => {
                    return (
                        <li key={admin.user_id} style={{display: "flex", alignItems: "center", flexWrap: "wrap", gap: "3px"}}>
                            {admin.path && <img src={admin.path} style={{width: "25px", height: "25px", borderRadius: "50%", marginRight: "2px"}}/>}
                            {collectUserName(users.find(x => x.id == admin.user_id))}<br />
                            <Button
                                style={{ marginLeft: "10px" }}
                                icon="trash"
                                color="red"
                                compact={true}
                                hollow={true}
                                disabled={admins.length > 1 ? false : true}
                                onClick={() => del({ 'user_id': admin.user_id })}
                            />
                            <small style={{flexBasis: "100%"}}>Добавлен: {formatTimesmap(admin.created_at)}</small>
                        </li>
                    );
                })}
                {isAdding && <li>{handleAdding()}</li>}
            </ul>
            {!isAdding ? <Button text="Добавить" color="blue" compact={true} hollow={true} onClick={() => setIsAdding(true)} />
                : <Button text="Отмена" color="red" compact={true} hollow={true} onClick={() => setIsAdding(false)} />
            }
        </div>
    )
};

export default AdminsEdit;
