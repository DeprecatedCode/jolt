# Jolt

By Nate Ferrero. [@NateFerrero](http://twitter.com/NateFerrero)

### An EvolutionSDK Bundle

Visit [EvolutionSDK on GitHub](http://github.com/EvolutionSDK/EvolutionSDK) for more information. Jolt requires the [LHTML Bundle](http://github.com/EvolutionSDK/lhtml) to be installed as well, and all Jolt tags and files are written and parsed with LHTML.

## Jolt Template

To use a jolt template within an LHTML page, just use the jolt tag as follows, where `templateFile` is the relative path (with or without the `.jolt` extension) to the Jolt template:

```html
<:jolt template="templateFile" title="Talk to World">

	<placeholder1>
		<h1>Why, Hello World!</h1>
		<p>I am in placeholder #1<p>
	</placeholder1>

	<placeholder2>
		<h1>I am World</h1>
		<p>I am in placeholder #2<p>
	</placeholder2>

</:jolt>
```

And in `templateFile.jolt`:

```html
<!doctype html>
<html>
	<head>
		<title>{title}</title>
	</head>
	<body>

		<p>Header</p>

		<placeholder2 />

		<p>You say:</p>

		<placeholder1 />

	</body>
</html>
```

Therefore, when you navigated to the first LTHML page, it would be rendered as:

```html
<!doctype html>
<html>
	<head>
		<title>Talk to World</title>
	</head>
	<body>

		<p>Header</p>

		<h1>I am World</h1>
		<p>I am in placeholder #2<p>

		<p>You say:</p>

		<h1>Why, Hello World!</h1>
		<p>I am in placeholder #1<p>

	</body>
</html>
```

I hope that shows how flexible and convenient Jolt can be! If you didn't notice, all attributes on `<:jolt>` become scope variables available within the LHTML stack.

### Variables

More details and examples coming soon...

### Placeholders

More details and examples coming soon...

### Embedded Templates

More details and examples coming soon...

## Jolt Section

### Including Content

More details and examples coming soon...

### Outcluding Content

More details and examples coming soon...

### JavaScript Optimization

If you include the `/@jolt/jolt.js` javascript in your root LHTML section, your entire site will instantly become browseable without reloading the page using the `window.history.pushState()` API.