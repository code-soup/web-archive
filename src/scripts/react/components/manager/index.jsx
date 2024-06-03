import React, { useCallback, useEffect, useState } from 'react';
import SnapshotLoopItem from './snapshot-loop';
import SnapshotViewer from './snapshot-viewer';
import SnapshotSearch from './search';
import SnapshotPager from './pager';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const fetchSnapshots = (queryParams = {}) => {
	apiFetch.use(apiFetch.createRootURLMiddleware(window.WebArchive.root));

	const path = Object.keys(queryParams).length
		? addQueryArgs('/web-archive/v1/snapshots', queryParams)
		: '/web-archive/v1/snapshots';

	return apiFetch({
		path: path,
		method: 'GET',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			Accept: 'application/json',
			'X-WP-Nonce': window.WebArchive.nonce,
		},
	});
};

const WebArchiveManager = () => {
	const [items, setItems] = useState([]);
	const [selectedSnapshot, setSelectedSnapshot] = useState(null);
	const [args, setArgs] = useState({
		page: 1,
		per_page: 15,
		search: '',
	});

	const updateSnapshots = useCallback(() => {
		fetchSnapshots(args).then((res) => {
			setItems(res);
		});
	}, [args]);

	useEffect(() => {
		updateSnapshots();
	}, [updateSnapshots]);

	const handleViewSnapshot = (snapshot) => {
		setSelectedSnapshot(snapshot);
	};

	const handleSearch = (searchString) => {
		setArgs({ ...args, search: searchString, page: 1 });
	};

	const handlePageSelect = (pageNumber) => {
		setArgs({ ...args, page: pageNumber }); // Reset to page 1 on new search
	};

	return (
		<div className='wrap'>
			<h1 className='wp-heading-inline'>WebArchive Manager</h1>
			<hr className='wp-header-end' />
			<SnapshotSearch onSearch={handleSearch} />
			<table className='wp-list-table widefat striped table-view-list posts'>
				<thead>
					<tr>
						<th>Modified</th>
						<th>Page Title</th>
						<th>Page URL</th>
						<th>Status</th>
						<th>Modified by</th>
						<th>Actions</th>
					</tr>
				</thead>
				<SnapshotLoopItem
					snapshots={items}
					onSnapshotSelect={handleViewSnapshot}
				/>
			</table>

			<SnapshotPager
				args={args}
				snapshots={items}
				onPageSelect={handlePageSelect}
			/>

			{selectedSnapshot !== null && (
				<SnapshotViewer
					snapshot={selectedSnapshot}
					onSnapshotSelect={handleViewSnapshot}
				/>
			)}
		</div>
	);
};

export default WebArchiveManager;
