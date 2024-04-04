import React from 'react';
import ReactDOM from 'react-dom/client';

const WebArchiveManager = React.lazy(() =>
	import('./react/components/manager/index')
);

document.addEventListener('DOMContentLoaded', function () {
	const root = ReactDOM.createRoot(
		document.getElementById('web-archive-app')
	);

	root.render(
		<React.Suspense fallback={<div>Loading...</div>}>
			<React.StrictMode>
				<WebArchiveManager />
			</React.StrictMode>
		</React.Suspense>
	);
});
