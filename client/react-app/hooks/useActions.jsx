import { useDispatch } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as global from '../store/reducers/globalSlice';

export const useActions = () => {
	const dispatch = useDispatch();

	return { ...bindActionCreators(global, dispatch), dispatch };
};