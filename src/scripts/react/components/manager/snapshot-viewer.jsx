import React, { useEffect, useState } from 'react';

const SnapshotViewer = ({ snapshot, onSnapshotSelect }) => {
	const hideViewer = (event) => {
		event.preventDefault();
		onSnapshotSelect(null);
	};

	return (
		<div id='snapshot-viewer'>
			<div id='nav-bar'>
				<button
					className='close'
					onClick={hideViewer}
				>
					<span>&times;</span>
				</button>
			</div>
			<div id='iframe-wrap'>
				<iframe src={snapshot.url}></iframe>
			</div>
		</div>
	);
};

export default SnapshotViewer;
